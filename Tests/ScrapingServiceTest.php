<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\Scraper\Factory;
use TheWebSolver\Codegarage\PaymentCard\Attributes\Card;
use TheWebSolver\Codegarage\Scraper\Attributes\CollectUsing;
use TheWebSolver\Codegarage\Scraper\Service\ScrapingService;
use TheWebSolver\Codegarage\PaymentCard\Event\BraintreeCardTraced;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreeCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Tracer\WikiPaymentCardsTracer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NumericTransformer;
use TheWebSolver\Codegarage\PaymentCard\Proxy\BraintreeTransformerProxy;
use TheWebSolver\Codegarage\PaymentCard\Service\CommonCardsScrapingService;
use TheWebSolver\Codegarage\PaymentCard\Service\WikiCardTypeScrapingService;
use TheWebSolver\Codegarage\PaymentCard\Service\BraintreeCardTypeScrapingService;

class ScrapingServiceTest extends TestCase {
	public const RESOURCE_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'Resource';
	public const WIKI_CARDS         = self::RESOURCE_DIRECTORY . DIRECTORY_SEPARATOR . 'wiki-cards.php';
	public const WIKI_CARDS_INDEXED = self::RESOURCE_DIRECTORY . DIRECTORY_SEPARATOR . 'wiki-cards-indexed.php';

	#[Test]
	public function itParsesScrapedPaymentCardDetailsFromWikiSite(): void {
		$factory  = new Factory();
		$iterator = $factory->generateDataIterator( new WikiCardTypeScrapingService( new class() extends WikiPaymentCardsTracer {} ) );

		foreach ( require_once self::WIKI_CARDS as $expectedCard ) {
			$this->assertSame( $expectedCard, $iterator->current()->getArrayCopy(), 'Indexed card: ' . $expectedCard[0] );

			$iterator->next();
		}

		$this->assertFalse( $iterator->valid() );

		unset( $iterator, $expectedCard );

		$iterator = $factory->generateDataIterator( new WikiCardTypeScrapingService( new WikiPaymentCardsTracer() ) );
		$cards    = require_once self::WIKI_CARDS_INDEXED;

		foreach ( $cards as $expectedCard ) {
			// Remove ignored status column.
			unset( $expectedCard['status'] );

			$this->assertSame( $expectedCard, $iterator->current()->getArrayCopy(), 'Indexed card: ' . $expectedCard['name'] );

			$iterator->next();
		}

		$this->assertFalse( $iterator->valid() );
	}

	#[Test]
	public function itScrapesFromBraintreeGithub(): void {
		$tracer = new BraintreeCardTracer();
		$tracer->addTransformer( new BraintreeTransformerProxy() );

		$tracer->addEventListener(
			function ( BraintreeCardTraced $e ) {
				$e->tracer->setIndicesSource(
					new CollectUsing( Card::class, Card::Alias, Card::Name, Card::Alias, Card::IINRange, Card::Breakpoint, Card::Length, Card::Code )
				);
			}
		);

		$mastercard = $this->getMasterCard( new BraintreeCardTypeScrapingService( $tracer ) );

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

		$tracer = new BraintreeCardTracer();
		$tracer->addTransformer( new BraintreeTransformerProxy( numericToInteger: false ) );
		$mastercard = $this->getMasterCard( new BraintreeCardTypeScrapingService( $tracer ) );

		$this->assertSame( 'Mastercard', $mastercard[ Card::Name->value ] );
		$this->assertSame( [ '4', '8', '12' ], $mastercard[ Card::Breakpoint->value ] );
		$this->assertSame( [ '16' ], $mastercard[ Card::Length->value ] );
		$this->assertSame(
			[ [ '51','55' ], [ '2221','2229' ], [ '223','229' ], [ '23','26' ], [ '270','271' ], '2720' ],
			$mastercard[ Card::IINRange->value ]
		);
		$this->assertSame(
			[
				'name' => 'CVC',
				'size' => '3',
			],
			$mastercard[ Card::Code->value ]
		);

		$tracer = new BraintreeCardTracer();
		$tracer->addTransformer( new BraintreeTransformerProxy() );
		$service  = new CommonCardsScrapingService( new WikiCardTypeScrapingService( new WikiPaymentCardsTracer() ), new BraintreeCardTypeScrapingService( $tracer ) );
		$iterator = $service->parse();

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
			$iterator->current()
		);
	}

	#[Test]
	public function itInfersCardDetailsBasedOnlyIndicesProvided(): void {
		$tracer = new BraintreeCardTracer();

		$tracer
			->addTransformer( new BraintreeTransformerProxy() )
			->addEventListener(
				static function ( BraintreeCardTraced $e ) {
					$e->tracer->setIndicesSource( new CollectUsing( Card::class, Card::Alias, null, Card::Alias, Card::IINRange ) );
				}
			);

		$mastercard = $this->getMasterCard( new BraintreeCardTypeScrapingService( $tracer ) );

		$this->assertCount( 2, $mastercard );
	}

	#[Test]
	public function testDigitExtraction(): void {
		$string = '[775557777-8688889999, 45, 99, 5-6, [12,13,14], 622126–622925 (China UnionPay co-branded), 6011, 644-649, 65, 60400100–60420099, 353, 356 (RuPay-JCB co-branded)]';

		$extracted = ( new NumericTransformer() )->transform( $string, $this->createStub( self::class ) );

		$this->assertNotEmpty( $extracted );

		$this->assertSame(
			[ [ 775557777,8688889999 ],45,99,[ 5,6 ],[ 12,13,14 ],[ 622126,622925 ],6011,[ 644,649 ],65,[ 60400100,60420099 ],353,356 ],
			$extracted
		);
	}

	private function getMasterCard( ScrapingService $scraper ): array {
		if ( $scraper->withCachePath( self::RESOURCE_DIRECTORY, 'cards.ts' )->hasCache() ) {
			$iterator = $scraper->parse( $scraper->fromCache() );
		} else {
			$scraper->toCache( $scraper->scrape() );

			$iterator = $scraper->parse( $scraper->fromCache() );
		}

		$mastercard = null;

		while ( $iterator->valid() ) {
			if ( 'mastercard' === $iterator->key() ) {
				$mastercard = (array) $iterator->current();

				break;
			}

			$iterator->next();
		}

		return $mastercard;
	}
}
