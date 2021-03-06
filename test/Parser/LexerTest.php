<?php

namespace pcrov\JsonReader\Parser;

class LexerTest extends \PHPUnit_Framework_TestCase
{

    /** @var \IteratorAggregate */
    protected $bytestream;

    public function testGetLineNumber()
    {
        $bytestream = $this->bytestream;
        $bytestream->setString(":\n:\r:\r\n:\r\n:");
        $lexer = new Lexer($bytestream);
        $iterator = new \IteratorIterator($lexer);
        $iterator->rewind();
        $this->assertTrue($iterator->valid());

        $line = 1;
        foreach ($iterator as $_) {
            $this->assertSame($line, $lexer->getLineNumber());
            $line++;
        }
    }

    /** @dataProvider provideTestTokenization */
    public function testTokenization($input, $expectedToken, $expectedValue)
    {
        $bytestream = $this->bytestream;
        $bytestream->setString($input);
        $lexer = new \IteratorIterator(new Lexer($bytestream));
        $lexer->rewind();
        $this->assertTrue($lexer->valid());

        foreach ($lexer as $key => $value) {
            $this->assertSame($expectedToken, $key);
            $this->assertSame($expectedValue, $value);
        }
    }

    /** @dataProvider provideTestLexerError */
    public function testLexerError($input, $expectedMessage)
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedMessage);

        $bytestream = $this->bytestream;
        $bytestream->setString($input);
        $lexer = new \IteratorIterator(new Lexer($bytestream));
        foreach ($lexer as $_);
    }

    public function provideTestTokenization()
    {
        return [
            [
                '"foo"',
                Tokenizer::T_STRING, "foo"
            ],
            [
                " \t \"foo\" \n",
                Tokenizer::T_STRING, "foo"
            ],
            [
                '"\b"',
                Tokenizer::T_STRING, "\x8"
            ],
            [
                '"\""',
                Tokenizer::T_STRING, '"'
            ],
            [
                '"\\\\"',
                Tokenizer::T_STRING, '\\'
            ],
            [
                '"\/"',
                Tokenizer::T_STRING, '/'
            ],
            [
                '"\n"',
                Tokenizer::T_STRING, "\n"
            ],
            [
                '"\r"',
                Tokenizer::T_STRING, "\r"
            ],
            [
                '"\f"',
                Tokenizer::T_STRING, "\f"
            ],
            [
                '"\t"',
                Tokenizer::T_STRING, "\t"
            ],
            [
                '"\u0061"',
                Tokenizer::T_STRING, "a"
            ],
            [
                '"\u0000"',
                Tokenizer::T_STRING, "\0"
            ],
            [
                '"\u2028"',
                Tokenizer::T_STRING, "\u{2028}"
            ],
            [
                "\"\u{2028}\"",
                Tokenizer::T_STRING, "\u{2028}"
            ],
            [
                '"\u2029"',
                Tokenizer::T_STRING, "\u{2029}"
            ],
            [
                "\"\u{2029}\"",
                Tokenizer::T_STRING, "\u{2029}"
            ],
            [
                '"\uD83D\uDC18"',
                Tokenizer::T_STRING, "\u{1F418}"
            ],
            [
                "\"\x7f\"",
                Tokenizer::T_STRING, "\x7f"
            ],
            [
                '42',
                Tokenizer::T_NUMBER, '42'
            ],
            [
                '-0.8',
                Tokenizer::T_NUMBER, '-0.8'
            ],
            [
                '42e5',
                Tokenizer::T_NUMBER, '42e5'
            ],
            [
                '42.8e-5',
                Tokenizer::T_NUMBER, '42.8e-5'
            ],
            [
                'true',
                Tokenizer::T_TRUE, true
            ],
            [
                'false',
                Tokenizer::T_FALSE, false
            ],
            [
                'null',
                Tokenizer::T_NULL, null
            ],
            [
                ':',
                Tokenizer::T_COLON, null
            ],
            [
                ',',
                Tokenizer::T_COMMA, null
            ],
            [
                '[',
                Tokenizer::T_BEGIN_ARRAY, null
            ],
            [
                ']',
                Tokenizer::T_END_ARRAY, null
            ],
            [
                '{',
                Tokenizer::T_BEGIN_OBJECT, null
            ],
            [
                '}',
                Tokenizer::T_END_OBJECT, null
            ]
        ];
    }

    public function provideTestLexerError()
    {
        return [
            [
                '"',
                "Line 1: Unexpected end of file."
            ],
            [
                "\n\"",
                "Line 2: Unexpected end of file."
            ],
            [
                't',
                "Line 1: Unexpected end of file."
            ],
            [
                'faL',
                "Line 1: Unexpected 'L'."
            ],
            [
                'nul ',
                "Line 1: Unexpected ' '."
            ],
            [
                '-',
                "Line 1: Unexpected end of file."
            ],
            [
                '0.a',
                "Line 1: Unexpected 'a'."
            ],
            [
                '0.4eb',
                "Line 1: Unexpected 'b'."
            ],
            [
                '"\h"',
                "Line 1: Unexpected 'h'."
            ],
            [
                '"\u454Z"',
                "Line 1: Unexpected 'Z'."
            ],
            [
                '"\u454' . "\u{1F418}\"",
                "Line 1: Unexpected '\u{1F418}'."
            ],
            [
                "\"\x1e\"",
                "Line 1: Unexpected control character \\u{1E}."
            ],
            [
                "\"\n\"",
                "Line 1: Unexpected control character \\u{A}."
            ],
            [
                "\"\r\"",
                "Line 1: Unexpected control character \\u{D}."
            ],
            [
                "\"\x81\"",
                "Line 1: Ill-formed UTF-8 sequence 0x81."
            ],
            [
                "\"\xC0\xAF\"",
                "Line 1: Ill-formed UTF-8 sequence 0xC0 0xAF."
            ],
            [
                "\"\xE0\x9F\x80\"",
                "Line 1: Ill-formed UTF-8 sequence 0xE0 0x9F 0x80."
            ],
            [
                "\"\xED\xA0\x80\"",
                "Line 1: Ill-formed UTF-8 sequence 0xED 0xA0 0x80."
            ],
            [
                "\"\xF0\x8F\x80\x80\"",
                "Line 1: Ill-formed UTF-8 sequence 0xF0 0x8F 0x80 0x80."
            ],
            [
                "\"\xF4\xA0\x80\x80\"",
                "Line 1: Ill-formed UTF-8 sequence 0xF4 0xA0 0x80 0x80."
            ],
            [
                "\x7f",
                "Line 1: Unexpected non-printable character \\u{7F}."
            ],
            [
                '"\uDC18"',
                "Line 1: Unexpected UTF-16 low surrogate \\uDC18."
            ],
            [
                '"\uD83D\uD83D"',
                "Line 1: Expected UTF-16 low surrogate, got \\uD83D."
            ],
            [
                "\u{1F418}",
                "Line 1: Unexpected '\u{1F418}'."
            ],
            [
                "\xC0\x80",
                "Line 1: Ill-formed UTF-8 sequence 0xC0 0x80."
            ],
            [
                "\xE0\x00",
                "Line 1: Ill-formed UTF-8 sequence 0xE0."
            ]
        ];
    }

    protected function setUp()
    {
        $this->bytestream = new class() implements \IteratorAggregate
        {

            /** @var string */
            private $string = "";

            public function setString(string $string)
            {
                $this->string = $string;
            }

            public function getIterator() : \Generator
            {
                $string = $this->string;
                $length = strlen($string);
                for ($i = 0; $i < $length; $i++) {
                    yield $string[$i];
                }
            }
        };
    }
}
