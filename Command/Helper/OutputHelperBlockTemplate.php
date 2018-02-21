<?php

namespace Origammi\Bundle\EzAppBundle\Command\Helper;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class OutputHelperBlockTemplate
 *
 * @internal
 *
 * @package   Origammi\Bundle\EzAppBundle\Command\Helper
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class OutputHelperBlockTemplate
{
    const MAX_LINE_LENGTH = 120;
    const DEFAULT_VARS    = [
        '<label/>'   => '',
        '<space/>'   => '',
        '<message/>' => '',
    ];

    /**
     * @var OutputFormatter
     */
    private $formatter;

    /**
     * @var int
     */
    private $maxLength = self::MAX_LINE_LENGTH;

    /**
     * @var null|array
     */
    private $cache;

    /**
     * @var array
     */
    private $formats = [
        'message'    => '%s',
        'label'      => ' [%s] ',
        'line'       => '<label/><message/>',
        'borderLine' => '<repeat/>',
    ];

//    /**
//     * @var array
//     */
//    private $stylesMap = [];

    private $styles = [
        'line'    => 'fg=%fg%;bg=%bg%',
        'label'   => '',
        'space'   => '',
        'message' => '',
        'repeat'  => '',
    ];

    /**
     * @var array
     */
    private $parameters = [
        '%fg%' => 'default',
        '%bg%' => 'default',
    ];


    private $label = '';

    /**
     * @var string
     */
    private $border = '';

    /**
     * @var bool
     */
    private $escape = false;

    /**
     * @var bool
     */
    private $wordwrap = true;

    /**
     * @var bool
     */
    private $appendNewLine = false;

    /**
     * @var bool
     */
    private $prependNewLine = false;

    /**
     * @var int
     */
    private $labelLinePosition = 0;


    private static $predefinedTemplates = [
        'border1' => [
            'border'           => '',
            'labelFormat'      => ' %s ',
            'lineFormat'       => '<space/> | <message/> |',
            'labelLineFormat'  => '#<label/>| <message/> |',
            'borderLineFormat' => '<repeat/>|',
            'styles'           => [
                'line'    => 'fg=%fg%;bg=%bg%',
                'label'   => 'fg=%fg%;bg=%bg%',
                'message' => 'fg=%fg%;bg=%bg%',
            ],
        ],
        'border2' => [
            'border'               => '=',
            'labelFormat'          => '= %s =',
//            'messageFormat'   => ' %s ',
            'lineFormat'           => '  <message/>   = ',
            'borderLineFormat'     => '<label/><repeat/>#=',
            'lastBorderLineFormat' => '<repeat/>=',
            'styles'               => [
                'line'    => 'fg=%fg%;bg=default',
                'message' => 'fg=default;bg=default',
            ],
        ],
        'bg1'     => [
//            'prependNewLine'          => true,
//            'appendNewLine'          => true,
            'border'           => '- ',
            'labelFormat'      => '  %s  ',
            'messageFormat'    => ' %s ',
            'lineFormat'       => ' <label/> |<message/>| ',
            'borderLineFormat' => ' <label/> |<border/>| ',
            'styles'           => [
                'label'   => 'fg=%bg%;bg=%fg%',
//                'label'   => 'fg=%fg%;bg=%bg%',
                'message' => 'fg=default;bg=default',
                'repeat'  => 'fg=default;bg=default',
//                'space'   => 'fg=$color2;bg=$color1',
            ],
        ],
        'bg2'     => [
            'labelLinePosition' => 1,
            'border'            => '- ',
            'labelFormat'       => '  %s  ',
            'messageFormat'     => ' %s ',
            'lineFormat'        => '| <label/> |<message/>|',
//            'borderLineFormat' => '|<repeat/>|',
            'styles'            => [
                'label' => 'fg=%bg%;bg=%fg%',
            ],
        ],
    ];

    public static function create($config = null, OutputFormatterInterface $formatter = null, $maxLength = null)
    {
        if (is_scalar($config)) {
            if (isset(static::$predefinedTemplates[$config])) {
                $config = static::$predefinedTemplates[$config];
            } else {
                throw new \InvalidArgumentException(sprintf('There is no predefined block template with name `%s`. You can use one of [%s]', (string)$config, implode(', ', array_keys(static::$predefinedTemplates))));
            }
        }

        if (!is_int($maxLength)) {
            $application = new Application();
            $dimensions  = $application->getTerminalDimensions();

            $maxLength = $dimensions[0] ?: self::MAX_LINE_LENGTH;
        }

        $formatter = is_null($formatter) ? new OutputFormatter(true) : clone $formatter;

        return new self($formatter, $maxLength, $config);
    }

    /**
     * OutputHelperBlockTemplate constructor.
     *
     * @param OutputFormatterInterface $formatter
     * @param int                      $maxLength
     * @param array|null               $config
     */
    public function __construct(OutputFormatterInterface $formatter, $maxLength, array $config = null)
    {
        $this->formatter = $formatter;
        $this->maxLength = $maxLength;
        $this->formatter->setStyle('line', new OutputFormatterStyle());

        if (is_array($config)) {
            foreach ($config as $propName => $propValue) {
                if (method_exists($this, 'set' . ucfirst($propName))) {
                    $this->{'set' . ucfirst($propName)}($propValue);
                }
            }
        }
    }

    /**
     * @param null|string|int $type
     *
     * @return string
     */
    public function renderBorder($type = null)
    {
        $this->updateStyles();

        $labelVars = $this->getLabelVars($type);
        $border    = $this->createBorder($this->maxLength, $this->getBorderLineFormat($labelVars));

        return $border;
    }

    /**
     * @param string $format
     * @param int    $lineLength
     *
     * @return string
     */
    private function createBorder($lineLength, $format)
    {
        $borderLength = $lineLength - $this->strlen($format, false);

        return $this->format(sprintf($format, str_pad('', $borderLength, $this->border)));
    }

    /**
     * @param string|int $label
     *
     * @return array
     */
    private function getLabelVars($label = '')
    {
        $label  = $space = (string)$label;
        $length = 0;

        $message = sprintf('<message>%s</message>', $this->formats['message']);

        if ($label) {
            $label  = sprintf($this->formats['label'], $label);
            $length = $this->strlen($label);
            $label  = sprintf('<label>%s</label>', $label);
            $space  = sprintf('<space>%s%s</space>', str_repeat(' ', $length), '%s');
        }

        return [
            '<label/>'   => $label,
            '<message/>' => $message,
            '<space/>'   => $space,
            '<repeat/>'  => '<repeat>%s</repeat>',
        ];
    }

    private function updateFormats($label = '')
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $vars = $this->createVars();

//        if ($label) {
//            $label       = $this->getContentFormat('label', $label);
//            $space       = $this->getContentFormat('space', $label);
//            $space       = $this->wrapElement('space', str_repeat(' ', $this->strlen($label, false)));
//
//            $vars['space']       = sprintf($vars['space'], str_repeat(' ', $this->strlen($label, false)));
//        }


        $linesFormat                = [];
        $linesFormat['default']     = '%s';
        $linesFormat['line']        = $this->replaceVars('line');
        $linesFormat['border']      = $this->getFormat('borderLine');
        $linesFormat['lineLabel']   = $this->getFormat('label');
        $linesFormat['borderLabel'] = $this->getFormat('lastBorderLine', $linesFormat['border']);


//        $this->replace_text($linesFormat, )
        $linesLength               = [];
        $linesLength['default']    = $this->strlen($linesFormat['default']);
        $linesLength['label']      = $this->strlen($linesFormat['label']);
        $linesLength['border']     = $this->strlen($linesFormat['border']);
        $linesLength['lastBorder'] = $this->strlen($linesFormat['lastBorder']);
        $maxFormatLength           = array_map('max', $linesLength);

//        $this->cache = compact('linesFormat', 'vars');

        $message = sprintf('<message>%s</message>', $vars['message']);
        $label   = sprintf('<label>%s</label>', $label);
        $space   = sprintf('<space>%s</space>', $vars['space']);
        $label   = sprintf('<label>%s</label>', sprintf($this->formats['label'], $label));
        $space   = sprintf('<space>%s</space>', str_repeat(' ', $this->strlen($label)));
        $space   = sprintf('<space>%s</space>', str_repeat(' ', $this->strlen($label)));

        $maxFormatLength = max($lineFormatLength, $labelLineFormatLength);


    }


    /**
     * @param string|array $formatNames Format names ordered by priority
     * @param bool         $isLabel
     *
     * @return string
     */
    private function replaceVars($formatNames, $isLabel = false)
    {
        $vars = $this->createVars();

        $format = $this->getFormat($formatNames, 'line');
        if (true === $isLabel) {
            $vars['<label/>']   = '<space/>';
            $vars['<message/>'] = '<repeat/>';

        }

        return sprintf('<line>%s</line>', $this->replace_text($format, $vars ?: []));
    }

    private function createVars()
    {
        if ($this->cache['vars']) {
            return $this->cache['vars'];
        }

        $message = $this->getWrappedFormat('message');
        $label   = $space = (string)$this->label;

        if (strlen($label)) {
            $label       = $this->getWrappedFormat('label', $this->label);
            $space       = $this->getWrappedFormat('space');
            $spaceLength = max(0, $this->strlen($label, false) - $this->strlen($space, false));
            $space       = sprintf($space, str_repeat(' ', $spaceLength));

        }

        return $this->cache['vars'] = compact('message', 'label', 'space');
    }

    private function getWrappedFormat($name, $string = '%s')
    {
        $string = sprintf($this->getFormat($name, '%s'), $string);

        return sprintf('<%s>%s</%s>', $name, $string, $name);
    }

    /**
     * @param string $format
     * @param int    $lineLength
     *
     * @return string
     */
    private function createLine($line, $format, $maxLineLength, $labelRendered = false)
    {
//        $lineLength = $maxLineLength - $this->strlen($line, false);
//        $lineLength -= $labelPosition === $i ? $labelLineFormatLength : $lineFormatLength;
//        $line = OutputFormatter::escape($line) . str_repeat(' ', max(0, $lineLength));
//
//        $format = $labelPosition === $i ? $labelLineFormat : $lineFormat;
//        $line   = $this->format(sprintf($format, $line));
    }


    /**
     * @param string|array    $messages
     * @param null|string|int $type
     *
     * @return array
     */
    public function render($messages, $type = null)
    {
        $this->setLabel($type);

        $this->updateStyles();
        $this->updateFormats($type);

        $maxFormatLength = 20;
        $maxLineLength   = $this->calculateLineLength($messages, $maxFormatLength);

        $lines = self::wrapLines($messages, $maxLineLength - $maxFormatLength, $this->escape, $this->wordwrap);


        $labelPosition = min(count($lines) - 1, $this->labelLinePosition);

        foreach ($lines as $i => &$line) {

//            $this->createLine($line, [] $lineFormatLength);
            $lineLength = $maxLineLength - $this->strlen($line, false);
            $lineLength -= $labelPosition === $i ? $labelLineFormatLength : $lineFormatLength;
            $line       = OutputFormatter::escape($line) . str_repeat(' ', max(0, $lineLength));

            $format = $labelPosition === $i ? $labelLineFormat : $lineFormat;
            $line   = $this->format(sprintf($format, $line));
        }

        if ($this->hasBorder()) {
            array_unshift($lines, $this->createBorder($maxLineLength, $this->getBorderLineFormat($labelVars)));
            $lines[] = $this->createBorder($maxLineLength, $this->getLastBorderLineFormat($labelVars));
        }

        if ($this->appendNewLine) {
            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @param string|array $messages
     * @param int          $messageLength
     *
     * @param bool         $escape
     * @param bool         $wordwrap
     *
     * @return array
     */
    public static function wrapLines($messages, $messageLength, $escape = false, $wordwrap = true)
    {
        $messages = is_array($messages) ? array_values($messages) : array( $messages );
        $lines    = [];

        foreach ($messages as $key => $message) {
            if (true === $escape) {
                $message = OutputFormatter::escape($message);
//                $message = OutputFormatter::escapeTrailingBackslash($message);
            }

            if (true === $wordwrap) {
                $lines = array_merge($lines, explode(PHP_EOL, wordwrap($message, $messageLength, PHP_EOL, true)));
            } else {
                $lines[] = $message;
            }

            if (count($messages) > 1 && $key < count($messages) - 1) {
                $lines[] = '';
            }
        }

        return $lines;
    }

    /**
     * @param bool $newLine
     *
     * @return $this
     */
    public function setPrependNewLine($newLine)
    {
        $this->prependNewLine = (bool)$newLine;

        return $this;
    }

    /**
     * @param bool $newLine
     *
     * @return $this
     */
    public function setAppendNewLine($newLine)
    {
        $this->appendNewLine = (bool)$newLine;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrependNewLine()
    {
        return $this->prependNewLine;
    }

    /**
     * @return mixed|OutputFormatterStyleInterface
     */
    public function getDefaultStyle()
    {
        return $this->formatter->getStyle('line');
    }

    /**
     * @param string      $foreground
     * @param null|string $background
     * @param array|null  $options
     *
     * @return $this
     */
    public function setDefaultStyle($foreground, $background = null, array $options = null)
    {
        $this->getDefaultStyle()->setForeground($foreground);
        $this->getDefaultStyle()->setBackground($background);

        if (null !== $options) {
            $this->getDefaultStyle()->setOptions($options);
        }

        $this->parameters['%fg%'] = $foreground;
        $this->parameters['%bg%'] = $background;

        return $this;
    }

    /**
     * @param null|string $color
     *
     * @return $this
     */
    public function setForeground($color = null)
    {
        $this->getDefaultStyle()->setForeground($color);
        $this->parameters['%fg%'] = (string)$color ?: 'default';

        return $this;
    }

    /**
     * @param null|string $color
     *
     * @return $this
     */
    public function setBackground($color = null)
    {
        $this->getDefaultStyle()->setBackground($color);
        $this->parameters['%bg%'] = $color;

        return $this;
    }

    /**
     * Sets some specific style option.
     *
     * @param string $option The option name
     *
     * @return $this
     */
    public function setOption($option)
    {
        $this->getDefaultStyle()->setOption($option);

        return $this;
    }

    /**
     * Unsets some specific style option.
     *
     * @param string $option The option name
     *
     * @return $this
     */
    public function unsetOption($option)
    {
        $this->getDefaultStyle()->unsetOption($option);

        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options = [])
    {
        $this->getDefaultStyle()->setOptions($options);
        $this->parameters['%options%'] = $options;

        return $this;
    }

    /**
     * @param array $styles
     *
     * @return $this
     */
    public function setStyles(array $styles)
    {
        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }

        return $this;
    }

    /**
     * @param string          $name
     * @param null|string|int $value
     *
     * @return $this
     */
    public function setStyle($name, $value)
    {
        $this->styles[$name] = $value;

        return $this;
    }

    /**
     * @param bool $escape
     *
     * @return $this
     */
    public function setEscape($escape = false)
    {
        $this->escape = (bool)$escape;

        return $this;
    }

    /**
     * @param bool $wordwrap
     *
     * @return $this
     */
    public function setWordwrap($wordwrap = true)
    {
        $this->wordwrap = (bool)$wordwrap;

        return $this;
    }

    /**
     * @param string $border
     *
     * @return $this
     */
    public function setBorder($border)
    {
        $this->border = (string)$border;

        return $this;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param string $labelFormat
     *
     * @return $this
     */
    public function setLabelFormat($labelFormat)
    {
        $this->formats['label'] = $labelFormat;

        return $this;
    }

    /**
     * @param string $messageFormat
     *
     * @return $this
     */
    public function setMessageFormat($messageFormat)
    {
        $this->formats['message'] = $messageFormat;

        return $this;
    }

    /**
     * @param string $lineFormat
     *
     * @return $this
     */
    public function setLineFormat($lineFormat)
    {
        $this->formats['line'] = $lineFormat;

        return $this;
    }

    /**
     * @param string $labelLineFormat
     *
     * @return $this
     */
    public function setLabelLineFormat($labelLineFormat)
    {
        $this->formats['labelLine'] = $labelLineFormat;

        return $this;
    }

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setLabelLinePosition($position)
    {
        $this->labelLinePosition = max(-1, $position);

        return $this;
    }

    /**
     * @param string $borderLineFormat
     *
     * @return $this
     */
    public function setBorderLineFormat($borderLineFormat)
    {
        $this->formats['borderLine'] = $borderLineFormat;

        return $this;
    }

    /**
     * @param string $lastBorderLineFormat
     *
     * @return $this
     */
    public function setLastBorderLineFormat($lastBorderLineFormat)
    {
        $this->formats['lastBorderLine'] = $lastBorderLineFormat;

        return $this;
    }

    /**
     * @param string $string
     *
     * @return mixed|string
     */
    private function format($string)
    {
        $string = $this->formatter->format($string);

        return $string;
    }

//    private function wordwrap_html($str, $width = 75, $break = "\n")
//    {
//        $tagPositions = [];
//
//        $lines = explode($break, $str);
//        foreach ($lines as $line) {
////            dump(str_repeat('==', 40));
////            $strlen($newMessage) - $this->strlen($newMessage);
////            $newMessage = $this->htmlWrapThing($newMessage, $messageLength);
//            dump($line);
////            $lines = array_merge($lines, explode(PHP_EOL, $newMessage));
//        }
//    }

    /**
     * @param string|array $messages
     * @param int          $formatLength
     *
     * @return int
     */
    private function calculateLineLength($messages, $formatLength = 0)
    {
        $messages   = is_array($messages) ? array_values($messages) : array( $messages );
        $maxLength  = $this->maxLength - $formatLength;
        $lineLength = 0;

        if ($maxLength < 1) {
            return 0;
        }

        foreach ($messages as $key => $message) {
            if (true !== $this->escape) {
                $message = $this->removeDecoration($message, false);
            }
            $newLines = explode(PHP_EOL, $message);

            foreach ($newLines as $newMessage) {
                $newLineLength = $this->strlen($newMessage, false);

                if ($newLineLength >= $maxLength) {
                    $lineLength = $maxLength;
                    break 2;
                }

                $lineLength = max($newLineLength, $lineLength);
            }
        }

        return $lineLength + $formatLength;
    }

    /**
     * @param null|array|string $name
     * @param null|string       $rules
     */
    private function updateStyles($name = null, $rules = null)
    {
        if (is_string($name) && is_string($rules)) {
            if ('line' !== $name && !$rules) {
                $this->formatter->setStyle($name, $this->getDefaultStyle());

                return;
            }

            if (!$this->formatter->hasStyle($name)) {
                $style = new OutputFormatterStyle();
                $this->formatter->setStyle($name, $style);
            } else {
                $style = $this->formatter->getStyle($name);
            }

            $this->updateOutputFormatterStyle($rules, $style);
        }

        if (is_null($name)) {
            $name = $this->styles;
        }

        if (is_array($name)) {
            foreach ($name as $styleName => $styleRule) {
                $this->updateStyles($styleName, $styleRule);
            }

            return;
        }
    }

    /**
     * @param string                        $rules
     * @param OutputFormatterStyleInterface $style
     */
    private function updateOutputFormatterStyle($rules, OutputFormatterStyleInterface $style)
    {
        if (empty($rules)) {
            return;
        }

        $rules = array_filter(explode(';', $this->replace_text($rules, $this->parameters)));
        foreach ($rules as $rule) {
            $rule = explode('=', $rule);

            if (!count($rule)) {
                continue;
            }
            $key   = trim(array_shift($rule));
            $value = trim((string)array_shift($rule)) ?: null;

            switch ($key) {
                case 'bg';
                    $style->setBackground($value);
                    break;
                case 'fg';
                    $style->setForeground($value);
                    break;
                case 'options';
                    $value = $value ? array_filter(explode(',', $value)) : [];
                    $style->setOptions($value);
                    break;
            }
        }
    }

    /**
     * @return bool
     */
    private function hasBorder()
    {
        return $this->border && $this->formatter->isDecorated();
    }


    /**
     * @param string|array $formatNames Format names ordered by priority
     * @param array        $vars
     *
     * @return string
     */
    private function formatLine($formatNames, array $vars = [])
    {
        $format = $this->getFormat($formatNames, 'line');

        return sprintf('<line>%s</line>', $this->replace_text($format, $vars ?: []));
    }

    /**
     * @param array $vars
     *
     * @return string
     */
    private function getLineFormat($formatNames, array $vars = [])
    {
        $vars['<label/>'] = '<space/>';

        return $this->formatLine('line', $vars);
    }

//    /**
//     * @param array $vars
//     *
//     * @return string
//     */
//    private function getBorderFormat($formatNames, array $vars = [])
//    {
//        $vars['<label/>'] = '<space/>';
//        $vars['<message/>'] = '<repeat/>';
//
//        return $this->formatLine('line', $vars);
//    }

    /**
     * @param array $vars
     *
     * @return string
     */
    private function getLabelLineFormat(array $vars = [])
    {
        return $this->formatLine('labelLine', $vars);
    }

    /**
     * @param array|null $vars
     *
     * @return string
     */
    private function getBorderLineFormat(array $vars = [])
    {
        $vars['<message/>'] = '<repeat/>';

        return $this->formatLine('borderLine', $vars);
    }

    /**
     * @param array|null $vars
     *
     * @return string
     */
    private function getLastBorderLineFormat(array $vars = [])
    {
        $vars['<message/>'] = '<repeat/>';
        $vars['<label/>']   = '<space/>';

        return $this->formatLine('lastBorderLine,borderLine', $vars);
    }

    /**
     * @param string|array $names Format names ordered by priority
     * @param string       $fallbackFormat
     *
     * @return string
     *
     */
    private function getFormat($names, $fallbackFormat = '%s')
    {
        $names = is_string($names) ? explode(',', $names) : (array)$names;

        if (!empty($names)) {
            foreach ($names as $formatName) {
                $formatName = trim($formatName);
                if (isset($this->formats[$formatName]) && $this->formats[$formatName]) {
                    return $this->formats[$formatName];
                    break;
                }
            }
        }

        if (isset($this->formats[$fallbackFormat]) && $this->formats[$fallbackFormat]) {
            return $this->formats[$fallbackFormat];
        }

        return $fallbackFormat;
    }

    /**
     * @param string $string
     * @param bool   $stripTags
     *
     * @return int
     */
    private function strlen($string, $stripTags = true)
    {
        if (true === $stripTags) {
            $string = strip_tags($string);
        }

        return Helper::strlenWithoutDecoration($this->formatter, str_replace('%s', '', $string));
    }

    /**
     * @param string $string
     * @param bool   $stripTags
     *
     * @return mixed|string
     */
    private function removeDecoration($string, $stripTags = true)
    {
        if (true === $stripTags) {
            $string = strip_tags($string);
        }

        return Helper::removeDecoration($this->formatter, $string);
    }


    /**
     * @param string|array $text
     * @param array        $searchMap
     *
     * @return string|array
     */
    private function replace_text($text, array $searchMap)
    {
        if (is_array($text)) {

            foreach ($text as &$child) {
                $child = $this->replace_text($child, $searchMap);
            }

            return $text;
        }

        return str_replace(array_keys($searchMap), $searchMap, (string)$text);
    }

    private function format_keys(array $array, $formatKey = null, $formatValue = null)
    {
        $formated = [];

        foreach ($array as $key => $value) {
            if (is_string($formatKey)) {
                $key = sprintf($formatKey, is_int($key) ? ($key + 1) : $key);
            }
            if (is_string($formatValue) && is_scalar($value) && strlen((string)$value)) {
                $value = sprintf($formatValue, $value);
            }
            $formated[$key] = $value;
        }

        return $formated;
    }
}
