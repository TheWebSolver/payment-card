<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use Iterator;
use ArrayObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Traceable;
use TheWebSolver\Codegarage\Scraper\Attributes\CollectUsing;
use TheWebSolver\Codegarage\Scraper\Service\ScrapingService;
use TheWebSolver\Codegarage\PaymentCard\Tracer\WikiPaymentCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Event\BraintreePaymentCardTraced;
use TheWebSolver\Codegarage\PaymentCard\Enums\PaymentCardProperty as Card;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreePaymentCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Service\WikiPaymentCardScrapingService;
use TheWebSolver\Codegarage\PaymentCard\Service\CommonPaymentCardScrapingService;
use TheWebSolver\Codegarage\PaymentCard\Service\BraintreePaymentCardScrapingService;

class PaymentCardScrapingServiceTest extends TestCase {
	public const RESOURCE_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'Resource';
	public const WIKI_CARDS         = self::RESOURCE_DIRECTORY . DIRECTORY_SEPARATOR . 'wiki-cards.php';
	public const WIKI_CARDS_INDEXED = self::RESOURCE_DIRECTORY . DIRECTORY_SEPARATOR . 'wiki-cards-indexed.php';

	#[Test]
	public function itParsesScrapedPaymentCardDetailsFromWikiSite(): void {
		$iterator = ( new WikiPaymentCardScrapingService( new class() extends WikiPaymentCardTracer {} ) )->parse();

		/** @var non-empty-array<string|int> $expectedCard */
		foreach ( require_once self::WIKI_CARDS as $expectedCard ) { // @phpstan-ignore-line -- file returns indexed array.
			$this->assertSame( $expectedCard, $iterator->current()->getArrayCopy(), 'Indexed card: ' . ( $expectedCard[0] ) );

			$iterator->next();
		}

		$this->assertFalse( $iterator->valid() );

		unset( $iterator, $expectedCard );

		$iterator = ( new WikiPaymentCardScrapingService( new WikiPaymentCardTracer() ) )->parse();

		/** @var non-empty-array<string|int> $expectedCard */
		foreach ( require_once self::WIKI_CARDS_INDEXED as $expectedCard ) { // @phpstan-ignore-line - file return assoc array.
			// Remove ignored status column.
			unset( $expectedCard['status'] );

			$this->assertSame( $expectedCard, $iterator->current()->getArrayCopy(), 'Indexed card: ' . $expectedCard['name'] );

			$iterator->next();
		}

		$this->assertFalse( $iterator->valid() );
	}

	#[Test]
	public function itScrapesFromBraintreeGithub(): void {
		$tracer = new BraintreePaymentCardTracer();

		$tracer->addEventListener(
			function ( BraintreePaymentCardTraced $e ) {
				$e->tracer->setIndicesSource(
					new CollectUsing( Card::class, Card::Alias, Card::Name, Card::Alias, Card::IINRange, Card::Breakpoint, Card::Length, Card::Code )
				);
			}
		);

		$mastercard = $this->getBraintreeMastercard( new BraintreePaymentCardScrapingService( $tracer ) );

		$this->assertSame( [ 4, 8, 12 ], $mastercard[ Card::Breakpoint->value ] );
		$this->assertSame( [ 16 ], $mastercard[ Card::Length->value ] );
		$this->assertSame(
			[ [ 51, 55 ], [ 2221, 2229 ], [ 223, 229 ], [ 23, 26 ], [ 270, 271 ], 2720 ],
			$mastercard[ Card::IINRange->value ]
		);

		$this->assertSame(
			[
				'name' => 'CVC',
				'size' => 3,
			],
			$mastercard['code']
		);
	}

	#[Test]
	public function itScrapesCommonCardTypesFromWikiAndBraintree(): void {
		$iterator = ( new CommonPaymentCardScrapingService(
			new WikiPaymentCardScrapingService( new WikiPaymentCardTracer() ), // @phpstan-ignore-line
			new BraintreePaymentCardScrapingService( new BraintreePaymentCardTracer() )
		) )->parse();

		$this->assertSame( 'american-express', $iterator->key() );
		$this->assertSame(
			[
				Card::Name->value       => 'American Express',
				Card::Alias->value      => 'american-express',
				Card::IINRange->value   => [ 34,37 ],
				Card::Breakpoint->value => [ 4,10 ],
				Card::Length->value     => [ 15 ],
				Card::Code->value       => [
					'name' => 'CID',
					'size' => 4,
				],
			],
			$iterator->current()->getArrayCopy()
		);
	}

	#[Test]
	public function itInfersCardDetailsBasedOnlyIndicesProvided(): void {
		$tracer = new BraintreePaymentCardTracer();

		$tracer->addEventListener(
			static function ( BraintreePaymentCardTraced $e ) {
				$e->tracer->setIndicesSource( new CollectUsing( Card::class, Card::Alias, null, Card::Alias, Card::IINRange ) );
			}
		);

		$mastercard = $this->getBraintreeMastercard( new BraintreePaymentCardScrapingService( $tracer ) );

		$this->assertCount( 2, $mastercard );
	}

	/**
	 * @param ScrapingService<
	 *  Iterator<array-key,ArrayObject<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>>,
	 *  Indexable&Traceable<ArrayObject<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>,BraintreePaymentCardTraced>
	 * > $scraper
	 * @return mixed[]
	 */
	private function getBraintreeMastercard( ScrapingService $scraper ): array {
		if ( $scraper->withCachePath( self::RESOURCE_DIRECTORY, 'cards.ts' )->hasCache() ) {
			$iterator = $scraper->parse();
		} else {
			$scraper->toCache( $scraper->scrape() );

			$iterator = $scraper->parse();
		}

		$mastercard = null;

		while ( $iterator->valid() ) {
			if ( 'mastercard' === $iterator->key() ) {
				$mastercard = (array) $iterator->current();

				break;
			}

			$iterator->next();
		}

		return $mastercard ?? [];
	}
}
