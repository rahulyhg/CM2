<?php

namespace CM\CMBundle\Service;

use Doctrine\ORM\EntityManager;
use Sonata\IntlBundle\Locale\LocaleDetectorInterface;
use Sonata\IntlBundle\Timezone\TimezoneDetectorInterface;
use CM\CMBundle\Entity\Notification;
use CM\CMBundle\Entity\EntityUser;

class Helper
{
    private $em;

    protected $timezoneDetector;

    protected $localeDetector;

    public function __construct(
        EntityManager $em,
        TimezoneDetectorInterface $timezoneDetector,
        LocaleDetectorInterface $localeDetector
    )
    {
        $this->em = $em;
        $this->timezoneDetector = $timezoneDetector;
        $this->localeDetector = $localeDetector;
    }

    public static function className($object)
    {
        if (substr($object, -2, 2) == '[]') {
            $object = substr($object, 0, -2);
            $aggregate = true;
        }
        try {
            $name = new \ReflectionClass(is_string($object) ? $object : get_class($object));
        } catch (\Exception $e) {
            throw new \Exception($object.' is not a known class.');
        }
        return $name->getShortName().($aggregate ? '[]' : '');
    }

    public static function fullClassName($shortName)
    {
        switch ($shortName) {
            default:
                return 'CM\CMBundle\Entity\\'.$shortName;
                // throw new \Exception('add class name '.$shortName);
        }
    }

    public function dateTimeFormat($lang = 'js')
    {
        $formatter = new \IntlDateFormatter(
            $this->localeDetector->getLocale(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            $this->timezoneDetector->getTimezone(),
            \IntlDateFormatter::GREGORIAN,
            ''
        );

        if ($lang == 'js') {
            $format = str_replace('m', 'i', $formatter->getPattern());
            $format = str_replace('a', 'p', $format);
            if (strpos($format, 'h') !== false) {
                $format = str_replace('h', 'H', $format);
            } elseif (strpos($format, 'H') !== false) {
                $format = str_replace('H', 'h', $format);
            }
            return $format;
        } elseif ($lang == 'php') {
            return $formatter->getPattern();
        }
    }

    function getObject($object, $objectId, $container = null)
    {
        switch (self::className($object))
        {
            case 'Image':
                return $this->em->getRepository('CMBundle:Image')->getImagesByIds($objectId, array('limit' => 6));
            case 'Comment':
                return $this->em->getRepository('CMBundle:Comment')->findOneById($objectId);
            case 'Comment[]':
                return $this->em->getRepository('CMBundle:Comment')->getComments(array_slice($objectId, -4, 4));
            case 'Like':
                return $this->em->getRepository('CMBundle:Like')->findOneById($objectId);
            case 'Like[]':
                return $this->em->getRepository('CMBundle:Like')->getLikes(array_slice($objectId, -4, 4));
            case 'Fan':
                return $this->em->getRepository('CMBundle:Fan')->getFans($objectId);
            default:
                return null;
        }
    }

    /**
     * Returns subject replaced with regular expression matchs
     *
     * @param mixed $search        subject to search
     * @param array $replacePairs  array of search => replace pairs
     */
    public static function pregtr($search, $replacePairs)
    {
        return preg_replace(array_keys($replacePairs), array_values($replacePairs), $search);
    }

    /**
     * Truncates +text+ to the length of +length+ and replaces the last three characters with the +truncate_string+
     * if the +text+ is longer than +length+.
     */
    public static function truncate_text($text, $length = 30, $truncate_string = '...', $truncate_lastspace = false)
    {
        if ($text == '')
        {
            return '';
        }

        $mbstring = extension_loaded('mbstring');
        if($mbstring)
        {
           $old_encoding = mb_internal_encoding();
           @mb_internal_encoding(mb_detect_encoding($text));
        }
        $strlen = ($mbstring) ? 'mb_strlen' : 'strlen';
        $substr = ($mbstring) ? 'mb_substr' : 'substr';

        if ($strlen($text) > $length)
        {
            $truncate_text = $substr($text, 0, $length - $strlen($truncate_string));
            if ($truncate_lastspace)
            {
              $truncate_text = preg_replace('/\s+?(\S+)?$/', '', $truncate_text);
            }
            $text = $truncate_text.$truncate_string;
        }

        if ($mbstring)
        {
           @mb_internal_encoding($old_encoding);
        }

        return $text;
    }
}