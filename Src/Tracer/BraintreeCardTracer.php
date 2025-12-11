<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Tracer;

use Iterator;
use BackedEnum;
use DOMElement;
use LogicException;
use TheWebSolver\Codegarage\Scraper\Enums\EventAt;
use TheWebSolver\Codegarage\PaymentCard\Enums\Card;
use TheWebSolver\Codegarage\Scraper\Helper\Normalize;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Traceable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\Scraper\Traits\CollectorSource;
use TheWebSolver\Codegarage\Scraper\Attributes\CollectUsing;
use TheWebSolver\Codegarage\PaymentCard\Event\BraintreeCardTraced;

/** @template-implements Traceable<array<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>,BraintreeCardTraced> */
#[CollectUsing( Card::class, Card::Alias, Card::Name, Card::Alias, Card::IINRange, Card::Breakpoint, Card::Length, Card::Code )]
class BraintreeCardTracer implements Traceable, Indexable {
	use CollectorSource;

	final public const IGNORABLE_RAW_CONTENT_SEPARATOR = 'cardTypes: CardCollection = {';

	/** @example ' visa: { niceType: "Visa", type: "visa", patterns: [4], gaps: [4, 8, 12], lengths: [16, 18, 19], code: { name: "CVV", size: 3, }, } as BuiltInCreditCardType,' */
	final public const BUILTIN_CREDIT_CARD_TYPE_PATTERN = '/[ ]+["]?(?<typeValue>[\w\-]+)["]?[\:]+[ ]+{[ ]+(?<object>.*?})[, ]+}[ as BuiltInCreditCardType,]/';
	/** @placeholder `1:` Card properties, `2:` Card properties' initials. */
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<propertyName>[%1$s]+)(?<separator>\:[ ]+?)(?<everythingBeforeNextProperty>.*?(?=, ?[%2$s]+))(?<codePropertyValue>[\{]+.*?[\}]))';
	/** @placeholder `1:` static::methodName, `2`: EventAt::caseName, `3:` reason. */
	final public const USE_EVENT_LISTENER = 'Invalid invocation of "%1$s()". Use event listener for "%2$s" to %3$s.';
	final public const CARD_PROPERTIES    = [
		'niceType' => Card::Name,
		'type'     => Card::Alias,
		'patterns' => Card::IINRange,
		'gaps'     => Card::Breakpoint,
		'lengths'  => Card::Length,
		'code'     => Card::Code,
	];

	final public const INVALID_SOURCE_TYPE       = 'Source to be traced must be raw user-content string scraped from Braintree GitHub.';
	final public const INVALID_JS_OBJECT_PATTERN = 'Invalid JS Object pattern for extracting Braintree Github Card Types.';
	/** @placeholder: `%s:` String to extract card type. */
	final public const INVALID_CARD_OBJECT = 'Invalid JS Object for extracting Braintree GitHub Card property and its value. "%s" given.';
	/** @placeholder `1:` Card property names`, `2:` Additional error message suffix. */
	final public const INVALID_CARD_PROPERTIES = 'Braintree GitHub Card Type only supports properties: "%1$s"%2$s.';
	/** @placeholder `1:` Value type being used as an iterator key. */
	final public const INVALID_INDEX_VALUE = 'Value used as an index key can only be of string type. "%s" type given';

	/** @var Iterator<array-key,array<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>> */
	private Iterator $cardsGenerator;
	private CollectUsing $collectedUsing;
	/** @var ?Transformer<contravariant static,string|list<int|list<int>>|array{name:string,size:int}> */
	private ?Transformer $transformer = null;

	private ?BraintreeCardTraced $eventBeingDispatched = null;
	/** @var array<'Start'|'End',callable[]> */
	private array $eventListeners = [];
	/** @var array<'Start'|'End',bool> */
	private array $eventDispatchedStatus = [];

	/**
	|-------------------------------------------------------------------------------------------------
	| Artifacts used during each Card Type tracing. Cleared after each tracing iteration.
	|-------------------------------------------------------------------------------------------------
	 */

	private string $rawCardObject;
	private string $currentItemIndex;
	private int $currentIterationCount;

	public function resetTraced(): void {
		unset( $this->cardsGenerator, $this->collectedUsing, $this->currentItemIndex, $this->currentIterationCount );
	}

	public function resetHooks(): void {
		unset( $this->transformer );

		$this->eventListeners        = [];
		$this->eventDispatchedStatus = [];
	}

	/** @return string Property Names or Initials separated by given {@param $separator}. */
	public static function getPropNames( bool $initial = false, string $separator = '|' ): string {
		$props = array_keys( self::CARD_PROPERTIES );

		return implode( $separator, $initial ? array_map( static fn( string $p ) => $p[0], $props ) : $props );
	}

	/** @throws ScraperError When unsupported property name given. */
	public static function getCardEnumBy( string $property, string $source = '' ): Card {
		return self::CARD_PROPERTIES[ $property ] ?? throw ScraperError::trigger(
			self::INVALID_CARD_PROPERTIES,
			self::getPropNames( separator: '", "' ),
			( $source ? '' : ". \"{$property}\" is not a valid property" ) .
			( $source ? ". Property extraction source is :- {$source}" : '' )
		);
	}

	public static function getRegexPattern(): string {
		$define = sprintf( self::PATTERN_DEFINITION, self::getPropNames(), self::getPropNames( initial: true ) );

		return "/{$define}(?<property>(?&propertyName))(?&separator)(?<value>(?&everythingBeforeNextProperty)|(?&codePropertyValue))/";
	}

	public function inferFrom( string|DOMElement $source, bool $normalize ): void {
		$source instanceof DOMElement && throw new ScraperError( self::INVALID_SOURCE_TYPE );

		$this->dispatchEvent( $event = new BraintreeCardTraced( EventAt::Start, $source, $this ) );
		$this->hydrateIndicesSourceFromAttribute();

		try {
			$this->cardsGenerator = $event->getInferredCards();
		} catch ( LogicException ) {
			$normalize && $source = Normalize::controlsAndWhitespacesIn( $source );
			$content              = explode( self::IGNORABLE_RAW_CONTENT_SEPARATOR, $source, limit: 2 )[1] ?? null;
			$this->cardsGenerator = $this->createCardsGenerator( $content ?: throw new ScraperError( self::INVALID_SOURCE_TYPE ) );
		}
	}

	public function setIndicesSource( CollectUsing $collection ): void {
		( $this->eventBeingDispatched?->isTargeted( EventAt::Start ) ?? false )
			? $this->registerIndicesSource( $collection )
			: $this->throwEventListenerNotUsed( __FUNCTION__ );
	}

	public function addTransformer( Transformer $transformer ): static {
		$this->transformer = $transformer;

		return $this;
	}

	public function addEventListener( callable $listener, EventAt $eventAt = EventAt::Start ): static {
		$this->eventListeners[ $eventAt->name ][] = $listener;

		return $this;
	}

	public function hasTransformer(): bool {
		return isset( $this->transformer );
	}

	public function getData(): Iterator {
		if ( isset( $this->cardsGenerator ) ) {
			yield from $this->cardsGenerator;
		}
	}

	public function getIndicesSource(): ?CollectUsing {
		return $this->collectedUsing ?? null;
	}

	public function getCurrentItemIndex(): ?string {
		return $this->currentItemIndex ?? null;
	}

	public function getCurrentIterationCount( ?BackedEnum $type = null ): ?int {
		return $this->currentIterationCount ?? null;
	}

	/**
	 * @return Iterator<array-key,array<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>>
	 * @throws ScraperError When index key for Card collection is not of string type.
	 */
	private function createCardsGenerator( string $source ): Iterator {
		$collectedUsing = $this->getIndicesSource();

		foreach ( $this->cardsFrom( $source ) as $stringifiedCardTypeJSObject ) {
			$values = $this->infer( $stringifiedCardTypeJSObject );

			if ( $index = $collectedUsing?->indexKey ) {
				$valueAsKey = $values[ $index ] ?? null;

				is_string( $valueAsKey ) || throw ScraperError::trigger( self::INVALID_INDEX_VALUE, get_debug_type( $valueAsKey ) );

				yield $valueAsKey => $values;
			} else {
				yield $values;
			}
		}

		$this->dispatchEvent( new BraintreeCardTraced( EventAt::End, $source, $this ) );
	}

	/** @return list<string> */
	private function cardsFrom( string $source ): array {
		return preg_match_all( $pattern = self::BUILTIN_CREDIT_CARD_TYPE_PATTERN, $source, $matched )
			? $matched['object']
			: ScraperError::patternMismatch( 'Braintree GitHub Card Type', $pattern, $source );
	}

	/** @return array<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}> */
	private function infer( string $cardObject ): array {
		$this->rawCardObject = $cardObject;

		$details = preg_match_all( $pattern = $this->getRegexPattern(), $cardObject, $matched, PREG_SET_ORDER )
			? array_reduce( $matched, $this->reduceToCardProperties( ... ), initial: [] )
			: null;

		unset( $this->currentItemIndex, $this->currentIterationCount, $this->rawCardObject );

		return $details ?: ScraperError::patternMismatch( 'Braintree GitHub Card\'s JS Object', $pattern, $cardObject );
	}

	/**
	 * @param array{}  $properties
	 * @param string[] $matched
	 * @return array<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>
	 */
	private function reduceToCardProperties( array $properties, array $matched ): array {
		$card = $this->getCardEnumBy( $matched['property'], $this->rawCardObject );

		$this->registerCurrentItemIndexAndCount( $propertyName = $card->value );

		if ( $this->shouldInferProperty( $propertyName ) ) {
			$properties[ $propertyName ] = $this->transformer?->transform( $matched, $this ) ?? trim( $matched['value'], '"' );
		}

		return $properties;
	}

	private function registerCurrentItemIndexAndCount( string $cardPropertyName ): void {
		$this->currentIterationCount = ( $this->currentIterationCount ?? 0 ) + 1;

		( $cardPropertiesToTrace = $this->getIndicesSource()?->items )
			&& ( false !== array_search( $cardPropertyName, $cardPropertiesToTrace, strict: true ) )
			&& ( $this->currentItemIndex = $cardPropertyName );
	}

	private function shouldInferProperty( string $name ): bool {
		return ! $this->getIndicesSource() || $this->getCurrentItemIndex() === $name;
	}

	/** @param ?CollectUsing $collection */
	private function registerIndicesSource( ?CollectUsing $collection = null ): void {
		$collection ??= $this->collectableFromAttribute();

		$collection && ( $this->collectedUsing = $collection );
	}

	private function hydrateIndicesSourceFromAttribute(): void {
		$this->getIndicesSource() || $this->registerIndicesSource();
	}

	/** @param array<callable(BraintreeCardTraced):void> $listeners */
	private function tryListeningToDispatchedEvent( BraintreeCardTraced $event, array $listeners ): void {
		try {
			$this->eventBeingDispatched = $event;

			foreach ( $listeners as $listenTo ) {
				$listenTo( $event );
			}
		} finally {
			unset( $this->eventBeingDispatched );
		}
	}

	private function dispatchEvent( BraintreeCardTraced $event ): void {
		$listeners      = $this->eventListeners[ $event->scope() ] ?? [];
		$whenDispatched = $this->eventDispatchedStatus[ $event->scope() ] ?? false;

		if ( ! $listeners ) {
			$this->eventDispatchedStatus[ $event->scope() ] = $whenDispatched;

			return;
		}

		$this->eventDispatchedStatus[ $event->scope() ] = true;

		$this->tryListeningToDispatchedEvent( $event, $listeners );
	}

	private static function throwEventListenerNotUsed( string $methodName ): never {
		$eventAt = Normalize::case( EventAt::Start );

		throw ScraperError::trigger( self::USE_EVENT_LISTENER, static::class . '::' . $methodName, $eventAt, 'set Card Type property names' );
	}
}
