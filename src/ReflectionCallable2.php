<?php

namespace Chiron\Injector;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionObject;
use ReflectionMethod;
use Reflector;
use RuntimeException;

//https://github.com/Wandu/Reflection/blob/eca8daed402eb4706af6dd403879c88655f38b7d/src/ReflectionCallable.php
//https://github.com/fratily/reflection/blob/3abc9b9f5f6a766334ce61bd7be6f50307c0906d/src/ReflectionCallable.php
//https://github.com/eslym/php-router/blob/96e449b5869338d2b3c30eb3eeb99637c4349204/src/ReflectionCallable.php
//https://github.com/PHP-DI/Invoker/blob/master/src/Reflection/CallableReflection.php
//https://github.com/GreenCape/reflector/blob/master/src/GreenCape/Reflector.php
//https://github.com/Raphhh/trex-reflection/blob/master/src/CallableReflection.php
//https://github.com/Raphhh/trex/blob/master/src/TRex/Reflection/CallableReflection.php

class ReflectionCallable2 extends ReflectionFunctionAbstract implements Reflector
{

    /**
     * @var object
     */
    private $instance = null;

    /**
     * @var ReflectionMethod|ReflectionFunction
     */
    private $internal;

    /**
     * @var string
     */
    private $magic = null;

    /**
     * ReflectionCallable constructor.
     * @param callable $callable
     * @throws ReflectionException
     */
    public function __construct($callable)
    {
        /*
        if(!is_callable($callable)){
            $callable = is_object($callable) ?
                '(object)'.get_class($callable) :
                '('.gettype($callable).')'.$callable;
            throw new ReflectionException("'$callable' is not callable.");
        }*/

        if(is_array($callable)){
            list($obj, $method) = $callable;
            if(is_object($obj)){
                $this->instance = $obj;
            }
            if(method_exists($obj, $method)){
                $this->internal = new ReflectionMethod($obj, $method);
            }else{
                $this->magic = $method;
                $this->internal = $this->instance ?
                    new ReflectionMethod($obj, '__call') :
                    new ReflectionMethod($obj, '__callStatic');
            }
        }else if($callable instanceof Closure || function_exists($callable)){
            $this->internal = new ReflectionFunction($callable);
        }else if(is_string($callable)){
            list($class, $method) = explode('::', $callable);
            if(method_exists($class, $method)) {
                $this->internal = new ReflectionMethod($callable);
            }else{
                $this->magic = $method;
                $this->internal = new ReflectionMethod($class, '__callStatic');
            }
        }else{
            $this->instance = $callable;
            $this->internal = new ReflectionMethod($callable, '__invoke');
        }

        // change visibility of private and protected method to be invoked freely (not only limited to public method)
        if($this->internal instanceof ReflectionMethod){
            if (! $this->internal->isPublic()) {
                $this->internal->setAccessible(true);
            }
        }
    }

    /**
     * @param array ...$args
     * @return mixed
     */
    // TODO : méthode à conserver ????
    public function __invoke(...$args)
    {
        return $this->invokeArgs($args);
    }

    /**
     * @param array ...$args
     * @return mixed
     */
    public function invoke(...$args){
        return $this->invokeArgs($args);
    }

    /**
     * @param array $args
     * @return mixed
     */
    public function invokeArgs(array $args){
        if($this->internal instanceof ReflectionFunction){
            return $this->internal->invokeArgs($args);
        }
        if($this->isMagicCall()){
            return $this->internal->invokeArgs($this->instance, [$this->magic, $args]);
        }
        return $this->internal->invokeArgs($this->instance, $args);
    }

    /**
     * @return bool
     */
    public function isMagicCall(){
        return $this->magic !== null;
    }

    /**
     * @return string
     */
    public function getMagicName(){
        return $this->magic;
    }

    /**
     * @return ReflectionFunction|ReflectionMethod
     */
    public function getInternalReference(){
        return $this->internal;
    }

    /**
     * @return bool
     */
    public function isFunction(){
        return $this->internal instanceof ReflectionFunction;
    }

    /**
     * @return bool
     */
    public function isMethod(){
        return $this->internal instanceof ReflectionMethod;
    }

    /**
     * Exports
     * @link http://php.net/manual/en/reflector.export.php
     * @return string
     * @since 5.0
     */
    static function export()
    {
        return ReflectionFunctionAbstract::export();
    }

    /**
     * To string
     * @link http://php.net/manual/en/reflector.tostring.php
     * @return string
     * @since 5.0
     */
    function __toString()
    {
        return $this->internal->__toString();
    }

    public function inNamespace()
    {
        return $this->internal->inNamespace();
    }

    public function isClosure()
    {
        return $this->internal->isClosure();
    }

    public function isDeprecated()
    {
        return $this->internal->isDeprecated();
    }

    public function isInternal()
    {
        return $this->internal->isInternal();
    }

    public function isUserDefined()
    {
        return $this->internal->isUserDefined();
    }

    public function getClosureThis()
    {
        return $this->isMethod() ?
            $this->instance :
            $this->internal->getClosureThis();
    }

    public function getClosureScopeClass()
    {
        return $this->internal->getClosureScopeClass();
    }

    public function getDocComment()
    {
        return $this->internal->getDocComment();
    }

    public function getEndLine()
    {
        return $this->internal->getEndLine();
    }

    public function getExtension()
    {
        return $this->internal->getExtension();
    }

    public function getExtensionName()
    {
        return $this->internal->getExtensionName();
    }

    public function getFileName()
    {
        return $this->internal->getFileName();
    }

    public function getName()
    {
        return $this->internal->getName();
    }

    public function getNamespaceName()
    {
        return $this->internal->getNamespaceName();
    }

    public function getNumberOfParameters()
    {
        return $this->internal->getNumberOfParameters();
    }

    public function getNumberOfRequiredParameters()
    {
        return $this->internal->getNumberOfRequiredParameters();
    }

    public function getParameters()
    {
        return $this->internal->getParameters();
    }

    public function getReturnType()
    {
        return $this->internal->getReturnType();
    }

    public function getShortName()
    {
        return $this->internal->getShortName();
    }

    public function getStartLine()
    {
        return $this->internal->getStartLine();
    }

    public function getStaticVariables()
    {
        return $this->internal->getStaticVariables();
    }

    public function hasReturnType()
    {
        return $this->internal->hasReturnType();
    }

    public function returnsReference()
    {
        return $this->internal->returnsReference();
    }

    public function isGenerator()
    {
        return $this->internal->isGenerator();
    }

    public function isVariadic()
    {
        return $this->internal->isVariadic();
    }
}
