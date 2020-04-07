<?php
/*
Main methods to use:
 quoteFromDir($dir):
   Quotes from any of the fortune-files in the dir.
 getRandomQuote($file):
   Quotes from the specific file.

 Written by Henrik Aasted Sorensen, henrik@aasted.org
 Read more at http://www.aasted.org/quote
*/
class Fortune
{

    public function quoteFromDir($dir)
    {
        $amount = 0;
        $index = 0;

        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if (strpos($file, ".dat") != false) {
                    $len = strlen($file);
                    if (substr($file, $len - 4) == ".dat") {
                        $number = $this->getNumberOfQuotes($dir . "/" . $file);
                        $amount += $number;
                        $quotes[$index] = $amount;
                        $files[$index] = $file;
                        $index++;
                    }
                }
            }

            srand((double) microtime() * 1000000);
            $index = rand(0, $amount);
            $i = 0;

            while ($quotes[$i] < $index) {
                $i++;
            }

            return $this->getRandomQuote($dir . "/" . $files[$i]);
        }
        return -1;
    }

    /*
     Reads the number of quotes in the file.
    */
    public function getNumberOfQuotes($file)
    {
        $fd = fopen($file, "rb");
        $this->readLong($fd); // Just move over the first long. Might as well be fseek.
        $len =  $this->readLong($fd);
        fclose($fd);
        return $len;
    }
    /*
     Picks quote number $index from the dat-file in $file.
    */
    public function getExactQuote($file, $index)
    {
        if (is_file($file) == false) {
            echo "Input must be a file!<br/>";
            return;
        }

        if (($fd = fopen($file, "rb")) == false) {
            echo "Cannot open $file<br/>";
            return;
        }
        fseek($fd, 24 + 4 * $index);

        $phys_index = $this->readLong($fd);

        fclose($fd);

        $quotefile = substr($file, 0, strlen($file) - 4);

        if (($fd = fopen($quotefile, "rb")) == false) {
            echo "Cannot find file $quotefile!<br/>";
        }

        $res = $this->getQuote($fd, $phys_index);
        fclose($fd);

        return $res;
    }

    /*
     Returns a random quote from $file.
    */
    public function getRandomQuote($file)
    {
        $number = $this->getNumberOfQuotes($file);

        $index = rand(0, $number - 1);

        return $this->getExactQuote($file, $index);
    }

    /*
     Reads a quote from the specified index.
    */
    public function getQuote($fd, $index)
    {
        fseek($fd, $index);
        $line = "";
        $res = "";
        do {
            $res = $res . $line;
            $line = fgets($fd, 1024) . "<br>";
        } while (($line[0] != "%") && (!feof($fd)));

        return $res;
    }

    /*
     Gets indexes from the file pointed to by the filedescriptor $fd.
    */
    public function getIndices($fd)
    {
        fseek($fd, 24, SEEK_SET);
        $i = 0;

        while (feof($fd) == false) {
            $res[$i] = readLong($fd);
            $i++;
        }
        return $res;
    }

    public function readLong($fd)
    {
        $res = fread($fd, 4);
        $l = ord($res[3]);
        $l += ord($res[2]) << 8;
        $l += ord($res[1]) << 16;
        $l += ord($res[0]) << 24;
        return $l;
    }


    public function createIndexFile($file)
    {
        $fd = @fopen($file, "r");
        if ($fd == false) {
            echo "File error!";
            exit;
        }

        $i = 1;
        $length = 0;
        $longest = 0;
        $shortest = 100000;
        $indices[0] = 0;
        while (!feof($fd)) {
            $line = fgets($fd);
            if ($line == "%\n") {
                $indices[$i] = ftell($fd);
                $i++;
                if ($length > $longest) {
                    $longest = $length;
                }

                if ($length < $shortest) {
                    $shortest = $length;
                }

                $length = 0;
            } else {
                $length = $length + strlen($line);
            }
        }

        fclose($fd);

        $fd = @fopen($file . ".dat", "w");

        if ($fd == false) {
            echo "<!-- createIndexFile: Could not write to file....-->";
            exit;
        }

        // Write header.
        $this->writeLong($fd, 2);
        $this->writeLong($fd, count($indices));
        $this->writeLong($fd, $longest);
        $this->writeLong($fd, $shortest);
        $this->writeLong($fd, 0);
        $this->writeLong($fd, 37 << 24);

        for ($i = 0; $i < count($indices); $i++) {
            $this->writeLong($fd, $indices[$i]);
        }

        fclose($fd);
    }

    public function writeLong($fd, $l)
    {
        fwrite($fd, chr(($l >> 24) & 255));
        fwrite($fd, chr(($l >> 16) & 255));
        fwrite($fd, chr(($l >> 8) & 255));
        fwrite($fd, chr($l & 255));
    }
}
