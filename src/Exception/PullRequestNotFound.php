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

namespace Localheinz\GitHub\ChangeLog\Exception;

final class PullRequestNotFound extends \RuntimeException implements ExceptionInterface
{
    public static function fromOwnerNameAndNumber(string $owner, string $name, int $number): self
    {
        return new self(\sprintf(
            'Could not find pull request "%d" in "%s/%s".',
            $number,
            $owner,
            $name
        ));
    }
}
