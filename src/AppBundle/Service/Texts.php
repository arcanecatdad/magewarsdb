<?php


namespace AppBundle\Service;

class Texts
{
	public function __construct($root_dir)
	{
        $config = \HTMLPurifier_Config::create(array('Cache.SerializerPath' => $root_dir));
        $this->purifier = new \HTMLPurifier($config);

        $this->transformer = new \Michelf\Markdown();
	}

    /**
     * Returns a substring of $string that is $max_length length max and doesn't split
     * a word or a html tag
     */
    public function truncate($string, $max_length)
    {
        $response = '';
        $token = '';

        $string = preg_replace('/\s+/', ' ', $string);

        while(strlen($token.$string) > 0 && strlen($response.$token) < $max_length)
        {
            $response = $response.$token;
            $matches = array();

            if(preg_match('/^(<.+?>)(.*)/', $string, $matches))
            {
                $token = $matches[1];
                $string = $matches[2];
            }
            else if(preg_match('/^([^\s]+\s*)(.*)/', $string, $matches))
            {
                $token = $matches[1];
                $string = $matches[2];
            }
            else
            {
                $token = $string;
                $string = '';
            }
        }
        if(strlen($token) > 0) {
            $response = $response . '[&hellip;]';
        }

        return $response;
    }

    /**
     * Returns the processed version of a markdown text
     */
    public function markdown($string)
    {
        return $this->img_responsive($this->purify($this->transform($string)));
    }

    /**
     * removes any dangerous code from a HTML string
     * @param unknown $string
     * @return string
     */
    public function purify($string)
    {
    	return $this->purifier->purify($string);
    }

    /**
     * turns a Markdown string into a HTML string
     * @param unknown $string
     * @return string
     */
    public function transform($string)
    {
    	return $this->transformer->transform($string);
    }

    /**
     * adds class="img-responsive" to every <img> tag
     * @param unknown $string
     * @return string
     */
    public function img_responsive($string)
    {
    	return preg_replace('/<img src=/', '<img data-src=', $string);
    }
}
