<?php

class NamespaceExposingTurtleParser extends EasyRdf_Parser_Turtle
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
     * Steps back, restoring the previous character or statement read() to the input buffer
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
}
