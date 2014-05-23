<?php
/**
* PHP-Proxy-Pattern
*
* Copyright (c) 2010, Julien Pauli <jpauli@php.net>.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions
* are met:
*
* * Redistributions of source code must retain the above copyright
* notice, this list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright
* notice, this list of conditions and the following disclaimer in
* the documentation and/or other materials provided with the
* distribution.
*
* * Neither the name of Julien Pauli nor the names of his
* contributors may be used to endorse or promote products derived
* from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
* FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
* COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
* LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
* ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* @author Julien Pauli <jpauli@php.net>
* @copyright 2010 Julien Pauli <jpauli@php.net>
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
*/

namespace Proxy\CacheAdapter;

/**
* APC Cache
*
* This class can be used as a cache backend for APC
*
* @author Julien Pauli <jpauli@php.net>
* @copyright 2010 Julien Pauli <jpauli@php.net>
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @see http://www.php.net/apc
* @version Release: @package_version@
*/
class Apc implements CacheInterface
{
    /**
     * Cache TTL
     *
     * @var int
     */
    private $cacheTime;

    /**
     * Constructor, just checks for the APC extension
     *
     * @throws \RuntimeException if ext/apc is not loaded
     */
    public function __construct()
    {
        if (!extension_loaded('APC') && !extension_loaded('APCu')) {
            throw new \RuntimeException("ext/apc or ext/apcu are required");
        }
    }

    /**
     * Retrieves an item from cache
     *
     * @param string $item item key
     * @return mixed The result
     */
    public function get(string $item)
    {
        return apc_fetch($item);
    }

    /**
     * Stores an item into the cache
     *
     * @param string $item The item key
     * @param mixed $value The value to store
     * @return CacheInterface
     */
    public function set(string $item, $value) : CacheInterface
    {
        apc_store($item, $value, $this->cacheTime);

        return $this;
    }

    /**
     * Checks if an item is in the cache
     *
     * @param string $item the item key
     * @return bool
     */
    public function has(string $item) : bool
    {
        return apc_fetch($item) !== false;
    }

    /**
     * Set the cache time to keep items in the cache
     *
     * @param int $time
     * @return Apc
     */
    public function setCacheTime(int $time) : CacheInterface
    {
        $this->cacheTime = $time;

        return $this;
    }

    /**
     * Gets the cache time for items
     *
     * @return int
     */
    public function getCacheTime() : int
    {
        return $this->cacheTime;
    }
}