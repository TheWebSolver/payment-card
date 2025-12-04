<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Service;

use Iterator;
use ArrayObject;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\Scraper\Traits\ScrapeYard;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Scrapable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Traceable;
use TheWebSolver\Codegarage\Scraper\Traits\ScraperSource;
use TheWebSolver\Codegarage\Scraper\Interfaces\TableTracer;
use TheWebSolver\Codegarage\PaymentCard\Event\BraintreeCardTraced;

/**
 * @template-implements Scrapable<
 *  Iterator<array-key,string|list<int|list<int>>|array{name:string,size:int}>,
 *  Traceable<mixed,object>
 * >
 */
class CommonCardsScrapingService implements Scrapable {
	use ScrapeYard, ScraperSource;

	/**
	 * @param Scrapable<Iterator<array-key,ArrayObject<array-key,string|list<int|list<int>>>>,TableTracer<string|list<int|list<int>>>> $tableService
	 * @param Scrapable<
	 *  Iterator<array-key,string|list<int|list<int>>|array{name:string,size:int}>,
	 *  Indexable&Traceable<string|list<int|list<int>>|array{name:string,size:int},BraintreeCardTraced>
	 * > $service
	 */
	public function __construct( private readonly Scrapable $tableService, private readonly Scrapable $service ) {}

	public function getTracer(): Traceable {
		throw new ScraperError( 'Common cards proxy does not implement its own tracer.' );
	}

	public function flush(): void {
		$this->tableService->flush();
		$this->service->flush();
	}

	public function defaultCachePath(): string {
		return CardFactory::RESOURCE_PATH;
	}

	public function parse(): Iterator {
		$commonCards = iterator_to_array( $this->service->parse() );
		$tableCards  = $this->tableService->parse();

		while ( $tableCards->valid() ) {
			$current = $tableCards->current()->getArrayCopy()['name'];

			assert( is_string( $current ) );

			$current = str_replace( ' ', '-', strtolower( $current ) );

			if ( isset( $commonCards[ $current ] ) ) {
				yield $current => $commonCards[ $current ];
			}

			$tableCards->next();
		}
	}
}
