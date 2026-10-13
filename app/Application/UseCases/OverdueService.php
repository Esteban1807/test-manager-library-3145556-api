<?php

namespace App\Application\UseCases;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Application\Ports\OverdueLine;

// HU-09: lines not returned whose due date is before now, most days late first (CA-09.1, CA-09.2)
final class OverdueService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly MemberRepository $members,
        private readonly Clock $clock,
    ) {}

    /** @return OverdueLine[] */
    public function execute(): array
    {
        $now = $this->clock->now();
        $lines = [];

        foreach ($this->loans->findWithOpenItems() as $loan) {
            $member = $this->members->findById($loan->memberId());

            foreach ($loan->items() as $item) {
                if (! $item->isOverdueAt($now)) {
                    continue;
                }

                $lines[] = new OverdueLine(
                    $loan->id(),
                    $item->id(),
                    $item->bookTitle(),
                    $member?->fullName() ?? '',
                    $item->dueAt(),
                    $item->daysLateAt($now),
                );
            }
        }

        usort($lines, fn (OverdueLine $a, OverdueLine $b) => $b->daysLate <=> $a->daysLate);

        return $lines;
    }
}
