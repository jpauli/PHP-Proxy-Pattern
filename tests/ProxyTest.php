<?php
use Proxy\CacheAdapter as Adapter;
use Proxy\Proxy;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\IsType;

class ProxyTest extends TestCase
{
    private $mockCache;
    private $mockSubject;
    private $proxy;

    public function setUp() : void
    {
        $this->proxy       = new Proxy();
        $this->mockCache   = new Adapter\Mock;
        $this->mockSubject = new \MockSubject();
        $this->proxy->setCacheObject($this->mockCache);
        $this->proxy->setSubjectObject($this->mockSubject);
    }

    public function assertPreconditions() : void
    {
        $this->assertSame($this->mockCache, $this->proxy->getCacheObject());
    }

    public function testHashFunctionOk()
    {
        $this->proxy->setHashFunction("sha1");
        $this->assertEquals("sha1", $this->proxy->getHashFunction());
    }

    /**
     * @dataProvider provideInvalidCallbacks
     */
    public function testHashFunctionKo(callable $callable)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->proxy->setHashFunction($callable);
    }

    public function testTimeoutIsGivenToCacheBackendByProxy()
    {
        $this->proxy->setCacheObject($this->mockCache, 20);
        $this->assertEquals(20, $this->proxy->getCacheObject()->getCacheTime());
    }

    public function testProxyWithAMissingSubjectObjectThrowsException()
    {
        $this->expectException(\DomainException::class);
        $p = new Proxy;
        $p->foo();
    }

    public function testProxyWithAMissingCachingObjectThrowsException()
    {
        $this->expectException(\DomainException::class);
        $p = new Proxy;
        $p->setSubjectObject($this->mockSubject);
        $p->foo();
    }

    public function testBadMethodCallOnProxyThrowsException()
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->proxy->mockCall(/*with no args*/);
    }

    public function testCallWithPHPErrorThrowsException()
    {
        $this->expectException(\ErrorException::class);
        $this->proxy->mockCallWithError();
    }

    public function testCallingANonExistantMethodOnProxyThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->proxy->foobarbaz();
    }

    public function testProxyProxiesAndCaches()
    {
        $arg = "foobar";
        $this->proxy->mockCall($arg);
        $hash = $this->proxy->makeHash([get_class($this->mockSubject), 'mockCall', [$arg]]);
        $this->assertIsString($this->mockCache->get($hash));
        $this->assertStringMatchesFormat(\MockSubject::MESSAGE, $this->mockCache->get($hash));
    }

    public function testProxyIncrementsCacheHits()
    {
        $arg = "foobar";
        $this->proxy->mockCall($arg);
        $this->assertEquals(0, $this->proxy->getCacheHits([get_class($this->mockSubject), 'mockCall'], [$arg]));

        $this->proxy->mockCall($arg);
        $this->assertEquals(1, $this->proxy->getCacheHits([get_class($this->mockSubject), 'mockCall'], [$arg]));

        $this->proxy->mockCall($arg."modified");
        $this->assertEquals(1, $this->proxy->getCacheHits([get_class($this->mockSubject), 'mockCall'], [$arg]));
    }

    public function testProxyLoadsDataFromCache()
    {
        $this->proxy->setSubjectObject($puMockSubject = $this->createMock(\MockSubject::class));
        $puMockSubject->expects($this->once()/*once and only once*/)
                      ->method("mockCall")
                      ->willReturn("return");

        $this->proxy->mockCall(0);

        $hash = $this->proxy->makeHash([get_class($puMockSubject), 'mockCall', [0]]);

        $this->assertTrue($this->mockCache->has($hash));
        $this->proxy->mockCall(0);
        $this->assertEquals(1, $this->proxy->getCacheHits($hash));
    }

    public function testCollision()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("not allowed");
        $this->proxy->setSubjectObject(new \Foo);
    }

    public function provideInvalidCallbacks()
    {
        /* valid callbacks take a string as first arg, and return a string */
        function foo() { }
        function foo1(array $a) { }
        function foo2(string $a) { }
        function foo3() : string { }
        function foo4(array $a) : string { }

        $class = new class {
            function foo() { }
            function foo1(array $a) { }
            function foo2(string $a) { }
            function foo3() : string { }
            function foo4(array $a) : string { }
            public function __invoke() { }
        };

        yield ['foo'];
        yield ['foo1'];
        yield ['foo2'];
        yield ['foo3'];
        yield ['foo4'];
        yield [[$class, 'foo']];
        yield [[$class, 'foo1']];
        yield [[$class, 'foo2']];
        yield [[$class, 'foo3']];
        yield [[$class, 'foo4']];
        yield [function () { }];
        yield [$class];
        yield ["MyDummyClass::foo"];
    }
}

class MyDummyClass { public static function foo() { } }