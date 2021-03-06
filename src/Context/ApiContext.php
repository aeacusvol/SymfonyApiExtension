<?php

namespace Persata\SymfonyApiExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Webmozart\Assert\Assert;

/**
 * Class ApiContext
 *
 * @package Persata\SymfonyApiExtension\Context
 */
class ApiContext extends RawApiContext
{
    /**
     * @Given /^the "([^"]*)" server parameter is "([^"]*)"$/
     */
    public function theServerParameter(string $key, string $value)
    {
        $this->getApiClient()->setServerParameter($key, $value);
    }

    /**
     * @Given /^the "([^"]*)" request header is "([^"]*)"$/
     */
    public function theRequestHeaderIs(string $key, string $value)
    {
        $this->getApiClient()->setRequestHeader($key, $value);
    }

    /**
     * @Given /^the request content type is JSON$/
     */
    public function theRequestContentTypeIsJSON()
    {
        $this->theRequestHeaderIs('Content-Type', 'application/json');
    }

    /**
     * @Given /^the request content type is XML/
     */
    public function theRequestContentTypeIsXML()
    {
        $this->theRequestHeaderIs('Content-Type', 'application/xml');
    }

    /**
     * @Given /^the request body is$/
     */
    public function theRequestBodyIs(PyStringNode $requestBody)
    {
        $this->getApiClient()->setRequestBody($requestBody);
    }

    /**
     * @Given /^the request query parameters are$/
     */
    public function theRequestQueryParametersAre(PyStringNode $requestQueryParameters)
    {
        $this->getApiClient()->setRequestParameters(json_decode($requestQueryParameters->getRaw(), true));
    }

    /**
     * @When /^the request is sent using (GET|POST|PUT|PATCH|DELETE|OPTIONS) to "([^"]*)"$/
     */
    public function theRequestIsSentTo(string $method, string $uri)
    {
        $this->getApiClient()->request($method, $uri);
    }

    /**
     * @Then /^the response status code should be (\d+)$/
     */
    public function theResponseStatusCodeShouldBe(int $statusCode)
    {
        Assert::same($this->getApiClient()->getResponse()->getStatusCode(), $statusCode);
    }

    /**
     * @Then /^the "([^"]*)" response header is "([^"]*)"$/
     */
    public function theResponseHeaderIs(string $key, string $value)
    {
        Assert::same($this->getApiClient()->getResponse()->headers->get($key), $value);
    }

    /**
     * @Then /^the response content type should be HTML$/
     */
    public function theResponseContentTypeShouldBeHTML()
    {
        $this->theResponseContentTypeShouldStartWith('text/html');
    }

    /**
     * @Then /^the response content type should be JSON$/
     */
    public function theResponseContentTypeShouldBeJSON()
    {
        $this->theResponseContentTypeShouldStartWith('application/json');
    }

    /**
     * @Then /^the response content type should be XML$/
     */
    public function theResponseContentTypeShouldBeXML()
    {
        Assert::regex($this->getApiClient()->getResponse()->headers->get('content-type'), '/^.+\/xml/');
    }

    /**
     * @Given /^the response content type should be "([^"]*)"$/
     */
    public function theResponseContentTypeShouldBe(string $contentType)
    {
        Assert::same($this->getApiClient()->getResponse()->headers->get('content-type'), $contentType);
    }

    /**
     * @Then /^the response content type should start with "([^"]*)"$/
     */
    public function theResponseContentTypeShouldStartWith(string $contentType)
    {
        Assert::startsWith($this->getApiClient()->getResponse()->headers->get('content-type'), $contentType);
    }

    /**
     * @Then /^the response content should be valid JSON$/
     */
    public function theResponseContentShouldBeValidJSON()
    {
        json_decode($this->getApiClient()->getResponse()->getContent());
        Assert::same(0, json_last_error());
    }

    /**
     * @Then /^the JSON response should contain the key "([^"]*)"$/
     */
    public function theJSONResponseShouldContainTheKey(string $key)
    {
        Assert::keyExists(json_decode($this->getApiClient()->getResponse()->getContent(), true), $key);
    }

    /**
     * @Then /^the JSON response should be$/
     */
    public function theJSONResponseShouldBe(PyStringNode $expectedContentStringNode)
    {
        $expectedJson = json_decode($expectedContentStringNode->getRaw(), true);
        $responseJson = json_decode($this->getApiClient()->getResponse()->getContent(), true);
        Assert::same($expectedJson, $responseJson);
    }

    /**
     * @Then /^the JSON response should have the key "([^"]*)" equal to "([^"]*)"$/
     */
    public function theJSONResponseShouldHaveTheKeyEqualTo(string $key, string $value)
    {
        $responseJson = json_decode($this->getApiClient()->getResponse()->getContent(), true);

        Assert::keyExists($responseJson, $key);
        Assert::same($responseJson[$key], $value);
    }

    /**
     * @Then /^the JSON response should have the structure$/
     */
    public function theJSONResponseShouldHaveTheStructure(PyStringNode $rawJsonStringNode)
    {
        if (strpos($rawJsonStringNode->getRaw(), '{') === 0) {
            trigger_error(
                'Passing raw JSON to the structure test is deprecated and will be removed in v0.2. Please pass a PHP array instead.',
                E_USER_DEPRECATED
            );
            $expectedJsonStructure = json_decode($rawJsonStringNode->getRaw(), true);
        } else {
            $expectedJsonStructure = eval(sprintf('return %s;', $rawJsonStringNode->getRaw()));
        }

        $responseJson = json_decode($this->getApiClient()->getResponse()->getContent(), true);

        $this->assertJsonStructure(
            $expectedJsonStructure,
            $responseJson
        );
    }

    /**
     * @param array $expectedJsonStructure
     * @param array $responseJson
     */
    protected function assertJsonStructure($expectedJsonStructure, $responseJson)
    {
        foreach ($expectedJsonStructure as $key => $value) {
            if (is_array($value)) {
                if ($key === '*') {
                    Assert::isArray($responseJson);
                    foreach ($responseJson as $responseJsonItem) {
                        $this->assertJsonStructure($expectedJsonStructure['*'], $responseJsonItem);
                    }
                } else {
                    Assert::keyExists($responseJson, $key);
                    $this->assertJsonStructure($expectedJsonStructure[$key], $responseJson[$key]);
                }
            } else {
                Assert::keyExists($responseJson, $value);
            }
        }
    }

    /**
     * @Then /^the XML response root should have attribute "([^"]*)" equal to "([^"]*)"$/
     */
    public function theXmlResponseShouldHaveAttributeEqualTo(string $name, string $value)
    {
        $responseXml = new \SimpleXMLElement($this->getApiClient()->getResponse()->getContent());
        Assert::eq($value, (string)$responseXml->attributes()->$name);
    }

    /**
     * @Then /^the XML response should have the child "([^"]*)"$/
     * @Then /^the XML response should have the child "([^"]*)" equal to "([^"]*)"$/
     */
    public function theXmlResponseShouldHaveTheChildEqualTo(string $child, $value = null)
    {
        $responseXml = new \SimpleXMLElement($this->getApiClient()->getResponse()->getContent());

        if ($value === null) {
            Assert::notEmpty((string)$responseXml->$child);
        } else {
            Assert::eq($value, (string)$responseXml->$child);
        }
    }

    /**
     * @Then the XML response should have the child :arg1 with the attribute :arg2 equal to :arg3
     */
    public function theXmlResponseShouldHaveTheChildWithTheAttributeEqualTo(string $child, $attribute, $value)
    {
        $responseXml = new \SimpleXMLElement($this->getApiClient()->getResponse()->getContent());
        Assert::eq($value, (string)$responseXml->$child->attributes()->$attribute);
    }

    /**
     * @Then /^the XML response should be$/
     */
    public function theXMLResponseShouldBe(PyStringNode $expectedContentStringNode)
    {
        $expectedXml = (new \SimpleXMLElement($expectedContentStringNode->getRaw()))->asXML();
        $responseXml = (new \SimpleXMLElement($this->getApiClient()->getResponse()->getContent()))->asXML();

        Assert::notEq($expectedXml, false);
        Assert::notEq($responseXml, false);

        Assert::eq($expectedXml, $responseXml);
    }
}
