<?php

declare(strict_types = 1);

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

class NoopCalculator extends AbstractCalculator
{
    protected const UNITS = 'posts';
    protected const AVG_PERIOD = "Month";

    protected $monthsTotal = [];
    protected $postsTotalByAuthor = [];

    /**
     * @inheritDoc
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $authorId = $postTo->getAuthorId();
        $yearMonth = $postTo->getDate()->format('YM');
        if( !in_array($yearMonth, $this->monthsTotal)){
            array_push($this->monthsTotal, $yearMonth);
        }
        $this->postsTotalByAuthor[$authorId] = ($this->postsTotalByAuthor[$authorId] ?? 0) + 1;
    }

    /**
     * @inheritDoc
     */
    protected function doCalculate(): StatisticsTo
    {
        $stats = new StatisticsTo();

        foreach ($this->postsTotalByAuthor as $authorId => $postCountByAuthor) {
            $avgAuthorPosts = round($postCountByAuthor / count($this->monthsTotal),2);

            $child = (new StatisticsTo())
                ->setName( $this->parameters->getStatName() )
                ->setValue($avgAuthorPosts)
                ->setUnits(self::UNITS)
                ->setSplitPeriod(self::AVG_PERIOD);
            $stats->addChild($child);
        }

        return $stats;
    }
}
