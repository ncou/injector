<?php

namespace Chiron\Injector\Test;

use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Chiron\Container\Container;
use Chiron\Injector\Injector;
use Chiron\Injector\CallableResolver;
use Chiron\Injector\Exception\NotCallableException;
use Chiron\Injector\MissingRequiredArgumentException;
use Chiron\Injector\Test\Support\ColorInterface;
use Chiron\Injector\Test\Support\EngineInterface;
use Chiron\Injector\Test\Support\EngineMarkTwo;
use Chiron\Injector\Test\Support\CallableSpy;


class CallableResolverTest extends TestCase
{
    /**
     * @var CallableResolver
     */
    private $resolver;
    /**
     * @var ArrayContainer
     */
    private $container;

    public function setUp(): void
    {
        $this->container = new Container;
        $this->resolver = new CallableResolver($this->container);
    }
    /**
     * @test
     */
    public function resolves_function()
    {
        $result = $this->resolver->resolve('strlen');
        $this->assertSame(strlen('Hello world!'), $result('Hello world!'));
    }
    /**
     * @test
     */
    public function resolves_namespaced_function()
    {
        $result = $this->resolver->resolve(__NAMESPACE__ . '\foo');
        $this->assertEquals('bar', $result());
    }
    /**
     * @test
     */
    public function resolves_callable_from_container()
    {
        $callable = function () {};
        $this->container->bind('thing-to-call', function() use ($callable) { return $callable;});
        $this->assertSame($callable, $this->resolver->resolve('thing-to-call'));
    }
    /**
     * @test
     */
    public function resolves_invokable_class()
    {
        $callable = new CallableSpy;
        $this->container->bind('Chiron\Injector\Test\Support\CallableSpy', function() use ($callable) { return $callable;});
        $this->assertSame($callable, $this->resolver->resolve('Chiron\Injector\Test\Support\CallableSpy'));
    }
    /**
     * @test
     */
    public function resolve_array_method_call()
    {
        $fixture = new InvokerTestFixture;
        $this->container->bind('Chiron\Injector\Test\InvokerTestFixture', $fixture);
        $result = $this->resolver->resolve(array('Chiron\Injector\Test\InvokerTestFixture', 'foo'));
        $result();
        $this->assertTrue($fixture->wasCalled);
    }
    /**
     * @test
     */
    public function resolve_string_method_call()
    {
        $fixture = new InvokerTestFixture;
        $this->container->bind('Chiron\Injector\Test\InvokerTestFixture', $fixture);
        $result = $this->resolver->resolve('Chiron\Injector\Test\InvokerTestFixture::foo');
        $result();
        $this->assertTrue($fixture->wasCalled);
    }
    /**
     * @test
     */
    public function resolves_array_method_call_with_service()
    {
        $fixture = new InvokerTestFixture;
        $this->container->bind('thing-to-call', $fixture);
        $result = $this->resolver->resolve(array('thing-to-call', 'foo'));
        $result();
        $this->assertTrue($fixture->wasCalled);
    }
    /**
     * @test
     */
    public function resolves_string_method_call_with_service()
    {
        $fixture = new InvokerTestFixture;
        $this->container->bind('thing-to-call', $fixture);
        $result = $this->resolver->resolve('thing-to-call::foo');
        $result();
        $this->assertTrue($fixture->wasCalled);
    }
    /**
     * @test
     */
    public function resolves_string_method_call_with_service_simple_doubledot()
    {
        $fixture = new InvokerTestFixture;
        $this->container->bind('thing-to-call', $fixture);
        $result = $this->resolver->resolve('thing-to-call:foo');
        $result();
        $this->assertTrue($fixture->wasCalled);
    }
    /**
     * @test
     * @expectedException \Chiron\Injector\Exception\NotCallableException
     * @expectedExceptionMessage 'foo' is neither a callable nor a valid container entry
     */
    public function throws_resolving_non_callable_from_container()
    {
        $resolver = new CallableResolver(new Container);
        $resolver->resolve('foo');
    }
    /**
     * @test
     * @expectedException \Chiron\Injector\Exception\NotCallableException
     * @expectedExceptionMessage Instance of stdClass is not a callable.
     */
    public function handles_objects_correctly_in_exception_message()
    {
        $resolver = new CallableResolver(new Container);
        $resolver->resolve(new \stdClass);
    }
    /**
     * @test
     * @expectedException \Chiron\Injector\Exception\NotCallableException
     * @expectedExceptionMessage stdClass::test() is not a callable.
     */
    public function handles_method_calls_correctly_in_exception_message()
    {
        $resolver = new CallableResolver(new Container);
        $resolver->resolve(array(new \stdClass, 'test'));
    }
}
function foo()
{
    return 'bar';
}
class InvokerTestFixture
{
    public $wasCalled = false;
    public function foo()
    {
        // Use this to make sure we are not called from a static context
        $this->wasCalled = true;
        return 'bar';
    }
}

