<?php declare(strict_types=1);
/*
 * (c) VisualSearch GmbH <office@visualsearch.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with the source code.
 */

namespace Vis\VisuallySearchProducts\Api\Client;

/**
 *
 */
interface VisuallySearchClientFactoryInterface
{
    /**
     * @return VisuallySearchClient
     */
    public function createClient(): VisuallySearchClient;
}
