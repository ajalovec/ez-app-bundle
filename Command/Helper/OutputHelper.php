<?php

/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\Helper;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\OutputStyle;

/**
 * Class OutputHelper
 *
 * @internal
 *
 * @package   Origammi\Bundle\EzAppBundle\Command\Helper
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class OutputHelper extends OutputStyle
{
    const MAX_LINE_LENGTH = 120;

    private $input;
    private $questionHelper;
    private $progressBar;
    private $lineLength;
    private $bufferedOutput;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
//        $clone = clone $output;
//        $clone->setFormatter(clone $output->getFormatter());

        $this->bufferedOutput = new BufferedOutput($output->getVerbosity(), false, clone $output->getFormatter());
        // Windows cmd wraps lines as soon as the terminal width is reached, whether there are following chars or not.
        $this->lineLength = min($this->getTerminalWidth() - (int)(DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);

        parent::__construct($output);
    }

    /**
     * {@inheritdoc}
     */
    public function listing(array $elements)
    {
        $this->autoPrependText();
        $elements = array_map(function ($element) {
            return sprintf(' * %s', $element);
        }, $elements);

        $this->writeln($elements);
        $this->newLine();
    }

    /**
     * @param string|array $messages
     * @param string       $style
     * @param string       $border
     */
    public function title($messages, $style = 'fg=cyan', $border = '=')
    {
        $messages  = is_array($messages) ? array_values($messages) : array( $messages );
        $maxLength = 0;

        foreach ($messages as $key => $message) {
            $maxLength      = max($maxLength, Helper::strlenWithoutDecoration($this->getFormatter(), $message));
            $messages[$key] = sprintf('<%s>%s</>', $style, OutputFormatter::escapeTrailingBackslash($message));
        }

        $messages[] = sprintf('<%s>%s</>', $style, str_repeat($border, $maxLength));

        $this->autoPrependBlock();
        $this->writeln($messages);
    }

    /**
     * {@inheritdoc}
     */
    public function section($message, $style = 'comment', $border = '-')
    {
        $this->title($message, $style, $border);
    }

    /**
     * {@inheritdoc}
     */
    public function text($message)
    {
        $this->autoPrependText();

        $messages = is_array($message) ? array_values($message) : array( $message );
        foreach ($messages as $message) {
            $this->writeln(sprintf('  %s', $message));
        }
    }

    public function hr($length = null, $color = null, $border = null)
    {
        $blockTemplate = $this->createBlockTemplate('yellow', null, []);

        $line   = str_repeat($border, $length ?: self::MAX_LINE_LENGTH);
        $color  = $color ?: 'default';
        $border = $border ?: '=';

        switch ($color) {
            case 'red':
            case 'green':
            case 'blue':
            case 'cyan':
            case 'magenta':
            case 'white':
            case 'black':
                $line = sprintf('<fg=%s>%s</>', $color, $line);
                break;
        }

//        $this->autoPrependBlock();
        $this->writeln($line);
//        $this->newLine();
    }

    /**
     * Formats a command comment.
     *
     * @param string|array $message
     * @param bool         $newLine
     */
    public function comment($message, $newLine = false)
    {
        $blockTemplate = OutputHelperBlockTemplate::create('bg1')
            ->setForeground('yellow')
            ->setBackground('black')
//            ->setAppendNewLine(true)
//            ->setPrependNewLine(true)
        ;

//        dump($blockTemplate->hasBlockPadding());
//        dump($blockTemplate->hasTextPadding());
//        dump($blockTemplate->hasNewLine());
//        die;

        $this->block($message, $blockTemplate, null);
    }

    /**
     * {@inheritdoc}
     */
    public function note($message, $newLine = true)
    {
        $blockTemplate = $this->createBlockTemplate('yellow', null, 'border2');
        $this->block($message, $blockTemplate, 'NOTE');
    }

    /**
     * {@inheritdoc}
     */
    public function success($message, $newLine = true)
    {
        $blockTemplate = $this->createBlockTemplate('black', 'green', 'bg2');
        $this->block($message, $blockTemplate, 'SUCCESS');
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, $newLine = true)
    {
        $blockTemplate = $this->createBlockTemplate('white', 'red', 'bg1');
        $this->block($message, $blockTemplate, 'ERROR');
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, $newLine = true)
    {
        $blockTemplate = $this->createBlockTemplate('black', 'yellow', 'bg1');
        $this->block($message, $blockTemplate, 'WARNING');
    }

    /**
     * {@inheritdoc}
     */
    public function caution($message, $newLine = true)
    {
        $blockTemplate = $this->createBlockTemplate('black', 'red', 'bg1');
        $this->block($message, $blockTemplate, 'CAUTION');
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, $type = self::OUTPUT_NORMAL)
    {
        parent::writeln($messages, $type);
        $this->bufferedOutput->writeln($this->reduceBuffer($messages), $type);
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
    {
        parent::write($messages, $newline, $type);
        $this->bufferedOutput->write($this->reduceBuffer($messages), $newline, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function newLine($count = 1)
    {
        parent::newLine($count);
        $this->bufferedOutput->write(str_repeat("\n", $count));
    }

    /**
     * {@inheritdoc}
     */
    public function ask($question, $default = null, $validator = null)
    {
        $question = new Question($question, $default);
        $question->setValidator($validator);

        return $this->askQuestion($question);
    }

    /**
     * {@inheritdoc}
     */
    public function askHidden($question, $validator = null)
    {
        $question = new Question($question);

        $question->setHidden(true);
        $question->setValidator($validator);

        return $this->askQuestion($question);
    }

    /**
     * {@inheritdoc}
     */
    public function confirm($question, $default = true)
    {
        return $this->askQuestion(new ConfirmationQuestion($question, $default));
    }

    /**
     * {@inheritdoc}
     */
    public function choice($question, array $choices, $default = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);
        $question
            ->setAutocompleterValues(array_keys($choices));

        return $this->askQuestion($question);
    }

    public function multiChoice($question, array $choices, array $default = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);
        $question
            ->setMultiselect(true)
            ->setAutocompleterValues(array_keys($choices))
        ;

        return $this->askQuestion($question);
    }


    public function table(array $headers, array $rows)
    {

    }


//    /**
//     * {@inheritdoc}
//     */
//    public function table(array $headers, array $rows)
//    {
//        $style = clone Table::getStyleDefinition('symfony-style-guide');
//        $style->setCellHeaderFormat('<info>%s</info>');
//
//        $table = new Table($this);
//        $table->setHeaders($headers);
//        $table->setRows($rows);
//        $table->setStyle($style);
//
//        $table->render();
//        $this->newLine();
//    }

    /**
     * {@inheritdoc}
     */
    public function progressStart($max = 0)
    {
        $this->progressBar = $this->createProgressBar($max);
        $this->progressBar->start();
    }

    /**
     * {@inheritdoc}
     */
    public function progressAdvance($step = 1)
    {
        $this->getProgressBar()->advance($step);
    }

    /**
     * {@inheritdoc}
     */
    public function progressFinish()
    {
        $this->getProgressBar()->finish();
        $this->newLine(2);
        $this->progressBar = null;
    }

    /**
     * {@inheritdoc}
     */
    public function createProgressBar($max = 0)
    {
        $progressBar = parent::createProgressBar($max);

        if ('\\' !== DIRECTORY_SEPARATOR) {
            $progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
            $progressBar->setProgressCharacter('');
            $progressBar->setBarCharacter('▓'); // dark shade character \u2593
        }

        return $progressBar;
    }

    /**
     * @param Question $question
     *
     * @return string
     */
    public function askQuestion(Question $question)
    {
        if ($question instanceof ChoiceQuestion) {
            $choices     = $question->getChoices();
            $isAssoc     = $this->isAssoc($choices);
            $multiselect = $question->isMultiselect();
            $question
                ->setValidator(function ($selected) use ($choices, $isAssoc, $multiselect) {
                    $selected = trim($selected);

                    if (0 === Helper::strlen($selected)) {
                        // TODO: implement default value
                        throw new LogicException('A value is required.');
                    }

                    if ($multiselect) {
                        // Check for a separated comma values
                        if (!preg_match('/^[^,]+(?:,[^,]+)*$/', $selected, $matches)) {
                            throw new InvalidArgumentException(sprintf('Value "%s" is invalid', $selected));
                        }
                        $selectedChoices = explode(',', $selected);
                    } else {
                        $selectedChoices = array( $selected );
                    }

                    $multiselectChoices = array();

                    foreach ($selectedChoices as $selectedChoice) {
                        $selectedChoice = trim($selectedChoice);

                        if (!isset($choices[$selectedChoice])) {
                            throw new \InvalidArgumentException(sprintf('The provided answer is ambiguous. Value should be one of [%s].', implode(', ', array_keys($choices))));
                        }

                        $multiselectChoices[] = $isAssoc ? $selectedChoice : $choices[$selectedChoice];
                    }

                    if ($multiselect) {
                        return $multiselectChoices;
                    }

                    return current($multiselectChoices);
                });
        }


        if ($this->input->isInteractive()) {
            $this->autoPrependBlock();
        }

        if (!$this->questionHelper) {
            $this->questionHelper = new SymfonyQuestionHelper();
        }

        $answer = $this->questionHelper->ask($this->input, $this, $question);

        if ($this->input->isInteractive()) {
            $this->newLine();
            $this->bufferedOutput->write("\n");
        }

        return $answer;
    }

    /**
     * @param null $foregroundColor
     * @param null $backgroundColor
     * @param null $config
     *
     * @return OutputHelperBlockTemplate
     */
    private function createBlockTemplate($foregroundColor = null, $backgroundColor = null, $config = null)
    {
        $blockTemplate = OutputHelperBlockTemplate::create($config, $this->getFormatter(), $this->lineLength);

        return $blockTemplate
            ->setForeground($foregroundColor)
            ->setBackground($backgroundColor)
            ->setEscape(true)
            ;
    }

    /**
     * @param                           $messages
     * @param OutputHelperBlockTemplate $blockTemplate
     * @param null                      $type
     */
    private function block($messages, OutputHelperBlockTemplate $blockTemplate, $type = null)
    {
        if ($blockTemplate->isPrependNewLine()) {
            $this->autoPrependBlock();
        }
//        elseif ($blockTemplate->hasTextPadding()) {
//            $this->autoPrependText();
//        }

        $this->writeln($blockTemplate->render($messages, $type));
    }

    /**
     * @return ProgressBar
     */
    private function getProgressBar()
    {
        if (!$this->progressBar) {
            throw new RuntimeException('The ProgressBar is not started.');
        }

        return $this->progressBar;
    }

    private function isAssoc($array)
    {
        $matches = array_intersect_key($array, array_values($array));

        return count($matches) !== count($array);
    }

    private function getTerminalWidth()
    {
        $application = new Application();
        $dimensions  = $application->getTerminalDimensions();

        return $dimensions[0] ?: self::MAX_LINE_LENGTH;
    }

    private function autoPrependBlock()
    {
        $chars = substr(str_replace(PHP_EOL, "\n", $this->bufferedOutput->fetch()), -2);

        if (!isset($chars[0])) {
            return $this->newLine(); //empty history, so we should start with a new line.
        }
        //Prepend new line for each non LF chars (This means no blank line was output before)
        $this->newLine(2 - substr_count($chars, "\n"));
    }

    private function autoPrependText()
    {
        $fetched = $this->bufferedOutput->fetch();
        //Prepend new line if last char isn't EOL:
        if ("\n" !== substr($fetched, -1)) {
            $this->newLine();
        }
    }

    private function reduceBuffer($messages)
    {
        // We need to know if the two last chars are PHP_EOL
        // Preserve the last 4 chars inserted (PHP_EOL on windows is two chars) in the history buffer
        return array_map(function ($value) {
            return substr($value, -4);
        }, array_merge(array( $this->bufferedOutput->fetch() ), (array)$messages));
    }


}



