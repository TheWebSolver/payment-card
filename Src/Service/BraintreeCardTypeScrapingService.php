<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Service;

use Iterator;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Traceable;
use TheWebSolver\Codegarage\Scraper\Attributes\ScrapeFrom;
use TheWebSolver\Codegarage\Scraper\Service\ScrapingService;
use TheWebSolver\Codegarage\PaymentCard\Event\BraintreeCardTraced;
use TheWebSolver\Codegarage\PaymentCard\Proxy\BraintreeTransformerProxy;

/**
 * @template-extends ScrapingService<
 *  Iterator<array-key,string|list<int|list<int>|array{name:string,size:int}>>,
 *  Indexable&Traceable<string|list<int|list<int>|array{name:string,size:int}>,BraintreeCardTraced>
 * >
 */
#[ScrapeFrom( 'Braintree GitHub', 'https://raw.githubusercontent.com/braintree/credit-card-type/refs/heads/main/src/lib/card-types.ts', 'cards.ts' )]
class BraintreeCardTypeScrapingService extends ScrapingService {
	/** @param Indexable&Traceable<string|list<int|list<int>|array{name:string,size:int}>,BraintreeCardTraced> $tracer */
	public function __construct( Traceable $tracer, ?ScrapeFrom $scrapeFrom = null ) {
		parent::__construct( $tracer->addEventListener( $this->hydrateWithDefaultTransformers( ... ) ), $scrapeFrom );
	}

	public function defaultCachePath(): string {
		return CardFactory::RESOURCE_PATH;
	}

	public function parse(): Iterator {
		$this->getTracer()->inferFrom( $this->fromCache(), normalize: true );

		yield from $this->getTracer()->getData();
	}

	protected function hydrateWithDefaultTransformers( BraintreeCardTraced $e ): void {
		$e->tracer->hasTransformer() || $e->tracer->addTransformer( new BraintreeTransformerProxy() );
	}
}
