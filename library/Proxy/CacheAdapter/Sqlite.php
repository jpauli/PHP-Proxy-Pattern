<?php
namespace Proxy\CacheAdapter;

class Sqlite implements CacheInterface
{
    private $sqlite;

    private $cacheTime;

    private const DEFAULT_SQLITEFILE = "/tmp/ProxyCache.sq3";

    private const DEFAULT_QUERY      = "SELECT value FROM cache WHERE key='%s' AND time < %d";

    public function __construct(string $sqliteFile = self::DEFAULT_SQLITEFILE)
    {
        if (!extension_loaded('sqlite3')) {
            throw new \RuntimeException("ext/sqlite3 is needed");
        }
        if (!file_exists($sqliteFile)) {
            if ($sqliteFile == self::DEFAULT_SQLITEFILE) {
                touch($sqliteFile);
                $this->sqlite = new \Sqlite3($sqliteFile);
                $this->prepareTables();
                return;
            }
            throw new \InvalidArgumentException("File '$sqliteFile' not found");
        }
        if (!is_writable($sqliteFile) || !is_readable($sqliteFile)) {
            throw new \InvalidArgumentException("Cannot access file '$sqliteFile'");
        }
        $this->sqlite = new \Sqlite3($sqliteFile);
    }

    public function get(string $item)
    {
        return unserialize($this->sqlite->querySingle(
                    sprintf(self::DEFAULT_QUERY, $this->sqlite->escapeString($item), time()-$this->cacheTime)));
    }

    public function set(string $item, $value) : CacheInterface
    {
        $this->sqlite->exec("INSERT INTO cache (key, value, time) VALUES(
                                  '{$this->sqlite->escapeString($item)}',
                                  '{$this->sqlite->escapeString(serialize($value))}',
                                  ".time()."
                                  )");
        return $this;
    }

    public function has(string $item) : bool
    {
        return null != $this->sqlite->querySingle(
                 sprintf(self::DEFAULT_QUERY, $this->sqlite->escapeString($item), time()-$this->cacheTime));
    }

    public function setCacheTime(int $time) : CacheInterface
    {
        $this->cacheTime = $time;

        return $this;
    }

    private function prepareTables() : void
    {
        $this->sqlite->exec("DROP TABLE IF EXISTS cache");
        $this->sqlite->exec("CREATE TABLE cache (key CHAR, value CHAR, time INTEGER)");
    }

    public function getCacheTime() : int
    {
        return $this->cacheTime;
    }
}