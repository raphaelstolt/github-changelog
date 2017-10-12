<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Console;

use Github\Client;
use Localheinz\GitHub\ChangeLog\Console;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use PHPUnit\Framework;
use Refinery29\Test\Util\TestHelper;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateCommandTest extends Framework\TestCase
{
    use TestHelper;

    public function testHasName()
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock()
        );

        $this->assertSame('generate', $command->getName());
    }

    public function testHasDescription()
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock()
        );

        $this->assertSame('Generates a changelog from information found between commit references', $command->getDescription());
    }

    /**
     * @dataProvider providerArgument
     *
     * @param string $name
     * @param bool   $isRequired
     * @param string $description
     */
    public function testArgument(string $name, bool $isRequired, string $description)
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock()
        );

        $this->assertTrue($command->getDefinition()->hasArgument($name));

        /* @var Input\InputArgument $argument */
        $argument = $command->getDefinition()->getArgument($name);

        $this->assertSame($name, $argument->getName());
        $this->assertSame($isRequired, $argument->isRequired());
        $this->assertSame($description, $argument->getDescription());
    }

    public function providerArgument(): \Generator
    {
        $arguments = [
            'owner' => [
                true,
                'The owner, e.g., "localheinz"',
            ],
            'repository' => [
                true,
                'The repository, e.g. "github-changelog"',
            ],
            'start-reference' => [
                true,
                'The start reference, e.g. "1.0.0"',
            ],
            'end-reference' => [
                false,
                'The end reference, e.g. "1.1.0"',
            ],
        ];

        foreach ($arguments as $name => list($isRequired, $description)) {
            yield $name => [
                $name,
                $isRequired,
                $description,
            ];
        }
    }

    /**
     * @dataProvider providerOption
     *
     * @param string $name
     * @param string $shortcut
     * @param bool   $isValueRequired
     * @param string $description
     * @param mixed  $default
     */
    public function testOption(string $name, string $shortcut, bool $isValueRequired, string $description, $default)
    {
        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $this->createPullRequestRepositoryMock()
        );

        $this->assertTrue($command->getDefinition()->hasOption($name));

        /* @var Input\InputOption $option */
        $option = $command->getDefinition()->getOption($name);

        $this->assertSame($name, $option->getName());
        $this->assertSame($shortcut, $option->getShortcut());
        $this->assertSame($isValueRequired, $option->isValueRequired());
        $this->assertSame($description, $option->getDescription());
        $this->assertSame($default, $option->getDefault());
    }

    public function providerOption(): \Generator
    {
        $options = [
            'auth-token' => [
                'a',
                false,
                'The GitHub token',
                null,
            ],
            'template' => [
                't',
                false,
                'The template to use for rendering a pull request',
                '- %title% (#%id%)',
            ],
        ];

        foreach ($options as $name => list($shortcut, $isValueRequired, $description, $default)) {
            yield $name => [
                $name,
                $shortcut,
                $isValueRequired,
                $description,
                $default,
            ];
        }
    }

    public function testExecuteAuthenticatesIfTokenOptionIsGiven()
    {
        $authToken = $this->getFaker()->password();

        $client = $this->createClientMock();

        $client
            ->expects($this->once())
            ->method('authenticate')
            ->with(
                $this->equalTo($authToken),
                $this->equalTo(Client::AUTH_HTTP_TOKEN)
            );

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock());

        $command = new Console\GenerateCommand(
            $client,
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'owner' => 'localheinz',
            'repository' => 'github-changelog',
            'start-reference' => '0.1.0',
            '--auth-token' => $authToken,
        ]);
    }

    public function testExecuteDelegatesToPullRequestRepository()
    {
        $faker = $this->getFaker();

        $owner = $faker->unique()->userName;
        $repository = $faker->unique()->slug();
        $startReference = $faker->unique()->sha1;
        $endReference = $faker->unique()->sha1;

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->once())
            ->method('items')
            ->with(
                $this->equalTo($owner),
                $this->equalTo($repository),
                $this->equalTo($startReference),
                $this->equalTo($endReference)
            )
            ->willReturn($this->createRangeMock([]));

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ]);
    }

    public function testExecuteRendersMessageIfNoPullRequestsWereFound()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $expectedMessage = 'Could not find any pull requests';

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock());

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertRegExp('@' . $expectedMessage . '@', $tester->getDisplay());
    }

    public function testExecuteRendersDifferentMessageIfNoPullRequestsWereFoundAndNoEndReferenceWasGiven()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;

        $expectedMessage = 'Could not find any pull requests';

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock());

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => null,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteRendersPullRequestsWithTemplate()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $endReference = $faker->sha1;
        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $template = '- %title% (#%id%)';

        $expectedMessages = [
            \sprintf(
                'Found %s pull requests',
                \count($pullRequests)
            ),
        ];

        \array_walk($pullRequests, function (Resource\PullRequestInterface $pullRequest) use (&$expectedMessages, $template) {
            $expectedMessages[] = \str_replace(
                [
                    '%title%',
                    '%id%',
                ],
                [
                    $pullRequest->title(),
                    $pullRequest->id(),
                ],
                $template
            );
        });

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock($pullRequests));

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => $endReference,
            '--template' => $template,
        ]);

        $this->assertSame(0, $exitCode);

        foreach ($expectedMessages as $expectedMessage) {
            $this->assertContains($expectedMessage, $tester->getDisplay());
        }
    }

    public function testExecuteRendersDifferentMessageWhenNoEndReferenceWasGiven()
    {
        $faker = $this->getFaker();

        $owner = $faker->userName;
        $repository = $faker->slug();
        $startReference = $faker->sha1;
        $count = $faker->numberBetween(1, 5);

        $pullRequests = $this->pullRequests($count);

        $expectedMessage = \sprintf(
            'Found %s pull requests',
            \count($pullRequests)
        );

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willReturn($this->createRangeMock($pullRequests));

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $owner,
            'repository' => $repository,
            'start-reference' => $startReference,
            'end-reference' => null,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    public function testExecuteHandlesExceptionsThrownWhenFetchingPullRequests()
    {
        $faker = $this->getFaker();

        $exception = new \Exception('Wait, this should not happen!');

        $pullRequestRepository = $this->createPullRequestRepositoryMock();

        $pullRequestRepository
            ->expects($this->any())
            ->method('items')
            ->willThrowException($exception);

        $expectedMessage = \sprintf(
            'An error occurred: %s',
            $exception->getMessage()
        );

        $command = new Console\GenerateCommand(
            $this->createClientMock(),
            $pullRequestRepository
        );

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'owner' => $faker->unique()->userName,
            'repository' => $faker->unique()->slug(),
            'start-reference' => $faker->unique()->sha1,
            'end-reference' => $faker->unique()->sha1,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains($expectedMessage, $tester->getDisplay());
    }

    /**
     * @return Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createClientMock()
    {
        return $this->createMock(Client::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Repository\PullRequestRepository
     */
    private function createPullRequestRepositoryMock()
    {
        return $this->createMock(Repository\PullRequestRepository::class);
    }

    /**
     * @param array $pullRequests
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Resource\RangeInterface
     */
    private function createRangeMock(array $pullRequests = [])
    {
        $range = $this->createMock(Resource\RangeInterface::class);

        $range
            ->expects($this->any())
            ->method('pullRequests')
            ->willReturn($pullRequests);

        return $range;
    }

    /**
     * @return Resource\PullRequest
     */
    private function pullRequest()
    {
        $faker = $this->getFaker();

        $id = $faker->unique()->numberBetween(1);
        $title = $faker->unique()->sentence();

        return new Resource\PullRequest(
            $id,
            $title
        );
    }

    /**
     * @param int $count
     *
     * @return Resource\PullRequest[]
     */
    private function pullRequests($count)
    {
        $pullRequests = [];

        for ($i = 0; $i < $count; ++$i) {
            \array_push($pullRequests, $this->pullRequest());
        }

        return $pullRequests;
    }
}