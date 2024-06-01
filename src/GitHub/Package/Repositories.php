<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\GitHub\Package;

use Joomla\Github\Package\Repositories as BaseRepositories;
use Joomla\Http\Response;

/**
 * Extended GitHub API Repositories class.
 */
class Repositories extends BaseRepositories
{
    /**
     * API Response object
     *
     * @var  Response
     */
    private $apiResponse;

    /**
     * Get the last API response if one is set
     *
     * @return  Response|null
     */
    public function getApiResponse()
    {
        return $this->apiResponse;
    }

    /**
     * Get a list of tags on a repository.
     *
     * Note: This is different from the parent `getListTags` method as it adds support for the API's pagination. This extended method can be removed
     * if the upstream class gains this support.
     *
     * @param   string   $owner  Repository owner.
     * @param   string   $repo   Repository name.
     * @param   integer  $page   The page number from which to get items.
     *
     * @return  object
     */
    public function getTags($owner, $repo, $page = 0)
    {
        // Build the request path.
        $path = '/repos/' . $owner . '/' . $repo . '/tags';

        // Send the request.
        $this->apiResponse = $this->client->get($this->fetchUrl($path, $page));

        return $this->processResponse($this->apiResponse);
    }
}
