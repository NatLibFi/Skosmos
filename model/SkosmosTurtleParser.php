<?php

class SkosmosTurtleParser extends EasyRdf\Parser\Turtle
{
    private $bytePos = 0;
    private $dataLength = null;

    /**
     * Returns the namespace prefixes as an array of prefix => URI
     * @return array $namespaces
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * A lot faster since we're now only reading the next 4 bytes 
     * instead of the whole string.
     * Gets the next character to be returned by read().
     */
    protected function peek()
    {
        if(!$this->dataLength) { $this->dataLength = strlen($this->data); }
        if ($this->dataLength > $this->bytePos) {
            $slice = substr($this->data, $this->bytePos, 4);
            return mb_substr($slice, 0, 1, "UTF-8");
        } else {
            return -1;
        }
    }

    /**
     * Does not manipulate the data variable. Keeps track of the
     * byte position instead.
     * Read a single character from the input buffer.
     * Returns -1 when the end of the file is reached.
     */
    protected function read()
    {
        $char = $this->peek();
        if ($char == -1) {
            return -1;
        }
        $this->bytePos += strlen($char);
        // Keep tracks of which line we are on (0A = Line Feed)
        if ($char == "\x0A") {
            $this->line += 1;
            $this->column = 1;
        } else {
            $this->column += 1;
        }
        return $char;
    }

    /**
     * Steps back, restoring the previous character or statement read() to the input buffer.
     */
    protected function unread($chars)
    {
        $this->column -= mb_strlen($chars, "UTF-8");
        $this->bytePos -= strlen($chars);
        if ($this->bytePos < 0) {
            $this->bytePos = 0;
        }
        if ($this->column < 1) {
            $this->column = 1;
        }
    }


    /**
     * Reverse skips through whitespace in 4 byte increments.
     * (Keeps the byte pointer accurate when unreading.)
     * @ignore
     */
    protected function unskipWS()
    {
        if ($this->bytePos - 4 > 0) {
            $slice = substr($this->data, $this->bytePos - 4, 4);
            while($slice != '') {
                if (!self::isWhitespace(mb_substr($slice, -1, 1, "UTF-8"))) {
                    return;
                }
                $slice = substr($slice, 0, -1);
                $this->bytePos -= 1;
            }
            // This 4 byte slice was full of whitespace.
            // We need to check that there isn't more in the next slice.
            $this->unSkipWS();
        }
    }

    /**
     * Parse triples with unskipWS (doesn't loose the pointer position in blank nodes).
     * @ignore
     */
    protected function parseTriples()
    {
        $c = $this->peek();

        // If the first character is an open bracket we need to decide which of
        // the two parsing methods for blank nodes to use
        if ($c == '[') {
            $c = $this->read();
            $this->skipWSC();
            $c = $this->peek();
            if ($c == ']') {
                $c = $this->read();
                $this->subject = $this->createBNode();
                $this->skipWSC();
                $this->parsePredicateObjectList();
            } else {
                $this->unskipWS();
                $this->unread('[');
                $this->subject = $this->parseImplicitBlank();
            }
            $this->skipWSC();
            $c = $this->peek();

            // if this is not the end of the statement, recurse into the list of
            // predicate and objects, using the subject parsed above as the subject
            // of the statement.
            if ($c != '.') {
                $this->parsePredicateObjectList();
            }
        } else {
            $this->parseSubject();
            $this->skipWSC();
            $this->parsePredicateObjectList();
        }

        $this->subject = null;
        $this->predicate = null;
        $this->object = null;
    }

}
