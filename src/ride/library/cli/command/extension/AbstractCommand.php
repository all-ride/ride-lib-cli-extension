<?php

namespace ride\library\cli\command\extension;

use ride\library\cli\command\AbstractCommand as BaseCommand;
use ride\library\cli\command\AutoCompleteStringArray;
use ride\library\cli\input\AutoCompletableInput;
use ride\library\cli\output\Output;

abstract class AbstractCommand extends BaseCommand {

    public function select(Output $output, $question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid.', $multiple = false) {
        $width = max(array_map('strlen', array_keys($choices)));

        foreach ($choices as $key => $choice) {
            $output->writeLine(sprintf(" [%-{$width}s] %s", $key, $choice));
        }

        $output->writeLine('');

        return $this->askAndValidate($output, $question, function ($picked) use ($choices, $errorMessage, $multiple) {
            if (null === $picked) {
                return null;
            }

            $selectedChoices = str_replace(' ', '', $picked);

            if ($multiple) {
                if (!preg_match('/^[a-zA-Z0-9_-]+(?:,[a-zA-Z0-9_-]+)*$/', $selectedChoices)) {
                    throw new \InvalidArgumentException(sprintf($errorMessage, $picked));
                }
                $selectedChoices = explode(',', $selectedChoices);
            } else {
                $selectedChoices = array($picked);
            }

            $multipleChoices = array();

            foreach ($selectedChoices as $choice) {
                if (empty($choices[$choice])) {
                    throw new \InvalidArgumentException(sprintf($errorMessage, $choice));
                }
                array_push($multipleChoices, $choice);
            }

            if ($multiple) {
                return $multipleChoices;
            }

            return $picked;
        }, $attempts, $default);
    }

    public function ask(Output $output, $question, $default = null, array $autocomplete = null) {
        if ($this->input instanceof AutoCompletableInput && null !== $autocomplete) {
            $this->input->addAutoCompletion(new AutoCompleteStringArray($autocomplete));
        }

        $return = $this->input->read($output, $question);

        return strlen($return) > 0 ? $return : $default;
    }

    public function askConfirmation(Output $output, $question, $default = true) {
        $answer = 'z';
        while ($answer && !in_array($answer, array('y', 'n'))) {
            $answer = strtolower($this->ask($output, $question, $default));
        }

        if (false === $default) {
            return $answer && 'y' == $answer;
        }

        return !$answer && 'y' == $answer;
    }

    public function askAndValidate(Output $output, $question, $validator, $attempts = false, $default = null, array $autocomplete = null) {
        $self = $this;

        $interviewer = function () use ($output, $question, $default, $autocomplete, $self) {
            return $self->ask($output, $question, $default, $autocomplete);
        };

        return $this->validateAttempts($output, $interviewer, $validator, $attempts);
    }

    /**
     * @param \ride\library\cli\output\Output $output
     * @param callable                        $interviewer
     * @param callable                        $validator
     * @param false|integer                   $attempts
     * @return string
     * @throws \Exception
     */
    private function validateAttempts(Output $output, $interviewer, $validator, $attempts) {
        $error = null;

        while (false === $attempts || $attempts--) {
            if (null !== $error) {
                $output->writeErrorLine($error->getMessage());
            }

            try {
                return call_user_func($validator, $interviewer());
            } catch (\Exception $error) {
            }
        }

        throw $error;
    }
}