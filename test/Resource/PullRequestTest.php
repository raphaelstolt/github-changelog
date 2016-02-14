<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\GitHub\ChangeLog\Resource\PullRequest;
use Localheinz\GitHub\ChangeLog\Resource\PullRequestInterface;
use PHPUnit_Framework_TestCase;
use Refinery29\Test\Util\Faker\GeneratorTrait;

class PullRequestTest extends PHPUnit_Framework_TestCase
{
    use GeneratorTrait;

    public function testIsFinal()
    {
        $reflectionClass = new \ReflectionClass(PullRequest::class);

        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testImplementsPullRequestInterface()
    {
        $reflectionClass = new \ReflectionClass(PullRequest::class);

        $this->assertTrue($reflectionClass->implementsInterface(PullRequestInterface::class));
    }

    public function testConstructorSetsIdAndTitle()
    {
        $faker = $this->getFaker();

        $id = $faker->randomNumber();
        $title = $faker->sentence();

        $entity = new Resource\PullRequest(
            $id,
            $title
        );

        $this->assertSame($id, $entity->id());
        $this->assertSame($title, $entity->title());
    }
}