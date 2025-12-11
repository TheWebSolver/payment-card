<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

use Iterator;
use LogicException;
use TheWebSolver\Codegarage\Scraper\Enums\EventAt;
use TheWebSolver\Codegarage\PaymentCard\Enums\Card;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreeCardTracer;

final class BraintreeCardTraced {
	private Iterator $inferredCards;

	public function __construct(
		public readonly EventAt $eventAt,
		public readonly string $target,
		public readonly BraintreeCardTracer $tracer
	) {}

	/** @return 'Start'|'End' EventAt case name. */
	public function scope(): string {
		return $this->eventAt->name;
	}

	public function isTargeted( EventAt $event ): bool {
		return $this->eventAt === $event;
	}

	/** @param Iterator<array-key,array<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>> $iterator */
	public function setInferredCards( Iterator $iterator ): void {
		$this->inferredCards = $iterator;
	}

	/**
	 * @return Iterator<array-key,array<int|value-of<Card>,string|list<int|list<int>>|array{name:string,size:int}>>
	 * @throws LogicException When this method is invoked before iterator is set.
	 */
	public function getInferredCards(): Iterator {
		return $this->inferredCards ?? throw new LogicException( 'Traced Card Types not inferred yet.' );
	}
}
