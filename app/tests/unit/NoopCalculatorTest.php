<?php

declare(strict_types = 1);

namespace Tests\unit;

use \Datetime;
use \ReflectionClass;
use PHPUnit\Framework\TestCase;
use SocialPost\Dto\SocialPostTo;
use SocialPost\Driver\FictionalDriverFactory;
use Statistics\Dto\StatisticsTo;
use Statistics\Dto\ParamsTo;
use Statistics\Calculator\NoopCalculator;
use SocialPost\Hydrator\FictionalPostHydrator;
use Statistics\Calculator\CalculatorComposite;
use App\Controller\Factory\StatisticsControllerFactory;

class NoopCalculatorTest extends TestCase{

    private static $data;
    private static $token_response;
    private static $handledPosts = [];
    private static $noop;

    public static function setUpBeforeClass(): void{
        self::$data = file_get_contents( getcwd()."/tests/data/social-posts-response.json");
        self::$token_response = file_get_contents( getcwd()."/tests/data/auth-token-response.json");
        self::handlePosts();
        self::populateNoop();
    }

    public static function handlePosts():void {
        $posts = json_decode(self::$data, true);
        $hydrator = new FictionalPostHydrator();

        foreach($posts['data']['posts'] AS $post){ 
            $handledPost = $hydrator->hydrate($post);
            array_push( self::$handledPosts, $handledPost );
        }
    }

    public static function populateNoop(): void{
        $reflection = new ReflectionClass('Statistics\Calculator\NoopCalculator');
        $accumulate = $reflection->getMethod("doAccumulate");
        $accumulate->setAccessible(true);

        self::$noop = new NoopCalculator();
        foreach( self::$handledPosts AS $dto){
            $accumulate->invokeArgs(self::$noop, [$dto]);
        }
    }

    public function testDoCalculate(): void{

        $reflection = new ReflectionClass('Statistics\Calculator\NoopCalculator');
        $calculate = $reflection->getMethod("doCalculate");
        $calculate->setAccessible(true);

        $params = new ParamsTo();
        $params->setStatName("avgPostsPerUserPerMonth");
        $params->setStartDate( new DateTime() );
        $params->setEndDate( new DateTime() );

        self::$noop->setParameters($params);
        $stats = $calculate->invokeArgs(self::$noop, []);

        $children = $stats->getChildren();
        $this->assertCount(4, $children );

        foreach( $children AS $child ){
            $this->assertIsFloat( $child->getValue() );
            $this->assertEquals(1.0, $child->getValue());
        }
        $this->assertTrue(true);


    }
}
