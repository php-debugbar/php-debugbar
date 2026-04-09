<?php

declare(strict_types=1);

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataFormatter\DataFormatter;
use DebugBar\DataFormatter\HtmlDataFormatter;
use DebugBar\DataFormatter\JsonDataFormatter;
use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;

class MessagesCollectorTest extends DebugBarTestCase
{
    public function testAddMessageAndLog(): void
    {
        $c = new MessagesCollector();
        $c->addMessage('foobar');
        $msgs = $c->getMessages();
        $this->assertCount(1, $msgs);
        $c->log('notice', 'hello');
        $this->assertCount(2, $c->getMessages());
    }

    public function testAggregate(): void
    {
        $a = new MessagesCollector('a');
        $c = new MessagesCollector('c');
        $c->aggregate($a);
        $c->addMessage('message from c');
        $a->addMessage('message from a');
        $msgs = $c->getMessages();
        $this->assertCount(2, $msgs);
        $this->assertArrayHasKey('collector', $msgs[1]);
        $this->assertEquals('a', $msgs[1]['collector']);
    }

    public function testCollect(): void
    {
        $c = new MessagesCollector();
        $c->addMessage('foo');
        $data = $c->collect();
        $this->assertEquals(1, $data['count']);
        $this->assertEquals($c->getMessages(), $data['messages']);
    }

    public function testReset(): void
    {
        $c = new MessagesCollector();
        $c->addMessage('foo');

        $c->reset();
        $data = $c->collect();
        $this->assertEquals(0, $data['count']);
    }

    public function testHtmlMessages(): void
    {
        $var = ['one', 'two'];

        $c = new MessagesCollector();
        $c->setDataFormatter(new DataFormatter());

        $this->assertFalse($c->isHtmlVarDumperUsed());
        $c->addMessage($var);
        $data = $c->collect();
        $message_text = $data['messages'][0]['message'];
        $this->assertStringContainsString('array', $message_text);
        $this->assertStringContainsString('one', $message_text);
        $this->assertStringContainsString('two', $message_text);
        $this->assertStringNotContainsString('span', $message_text);
        $this->assertNull($data['messages'][0]['message_html']);

        $c = new MessagesCollector();
        $c->setDataFormatter(new HtmlDataFormatter());

        $this->assertTrue($c->isHtmlVarDumperUsed());
        $c->addMessage($var);
        $data = $c->collect();
        // When HTML dumper is used, message is empty — content is in message_html
        $this->assertEmpty($data['messages'][0]['message']);
        $message_html = $data['messages'][0]['message_html'];
        $this->assertStringContainsString('array', $message_html);
        $this->assertStringContainsString('one', $message_html);
        $this->assertStringContainsString('two', $message_html);
        $this->assertStringContainsString('span', $message_html);
    }

    public function testJsonContextFormat(): void
    {
        $c = new MessagesCollector();
        $c->setDataFormatter(new JsonDataFormatter());

        $this->assertTrue($c->isJsonVarDumperUsed());

        $c->addMessage('hello {name}', 'info', ['name' => 'Alice', 'extra' => [1, 2, 3]]);
        $data = $c->collect();
        $msg = $data['messages'][0];

        // String messages are kept in message, not message_json
        $this->assertNull($msg['message_json']);
        $this->assertNotEmpty($msg['message']);

        // context values are in context_json, context keys are null
        $this->assertNotNull($msg['context_json']);
        $this->assertArrayHasKey('name', $msg['context_json']);
        $this->assertArrayHasKey('extra', $msg['context_json']);
        $this->assertSame('Alice', $msg['context_json']['name']);
        $this->assertSame([1, 2, 3], $msg['context_json']['extra']);

        // context keys exist but are null (cleared for JSON path)
        $this->assertArrayHasKey('name', $msg['context']);
        $this->assertNull($msg['context']['name']);
        $this->assertArrayHasKey('extra', $msg['context']);
        $this->assertNull($msg['context']['extra']);
    }

    public function testPlainContextFormat(): void
    {
        $c = new MessagesCollector();
        $c->setDataFormatter(new DataFormatter());

        $this->assertFalse($c->isJsonVarDumperUsed());
        $this->assertFalse($c->isHtmlVarDumperUsed());

        $c->addMessage('test', 'info', ['key' => 'value']);
        $data = $c->collect();
        $msg = $data['messages'][0];

        // Plain formatter: context holds formatted values, context_json is null
        $this->assertNull($msg['context_json']);
        $this->assertSame('value', $msg['context']['key']);
    }

    public function testTimeLabelForStringMessage(): void
    {
        $c = new MessagesCollector();
        $t = new TimeDataCollector();
        $c->setTimeDataCollector($t);

        $c->addMessage('hello world', 'info');

        $measures = $t->getMeasures();
        $this->assertCount(1, $measures);
        $this->assertEquals('[info]: hello world', $measures[0]['label']);
    }

    public function testTimeLabelForArrayMessage(): void
    {
        $c = new MessagesCollector();
        $c->setDataFormatter(new DataFormatter());
        $t = new TimeDataCollector();
        $c->setTimeDataCollector($t);

        $c->addMessage(['foo', 'bar', 'baz'], 'warning');

        $measures = $t->getMeasures();
        $this->assertCount(1, $measures);
        $this->assertEquals('[warning]: array(3)', $measures[0]['label']);
    }

    public function testTimeLabelForObjectMessage(): void
    {
        $c = new MessagesCollector();
        $c->setDataFormatter(new DataFormatter());
        $t = new TimeDataCollector();
        $c->setTimeDataCollector($t);

        $c->addMessage(new \stdClass(), 'debug');

        $measures = $t->getMeasures();
        $this->assertCount(1, $measures);
        $this->assertEquals('[debug]: stdClass', $measures[0]['label']);
    }

    public function testTimeLabelTruncatesLongMessages(): void
    {
        $c = new MessagesCollector();
        $t = new TimeDataCollector();
        $c->setTimeDataCollector($t);

        $longMessage = str_repeat('a', 200);
        $c->addMessage($longMessage, 'info');

        $measures = $t->getMeasures();
        $this->assertEquals('[info]: ' . str_repeat('a', 100), $measures[0]['label']);
    }
}
