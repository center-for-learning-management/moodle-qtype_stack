<?php
// This file is part of Stack - http://stack.maths.ed.ac.uk//
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();


require_once(__DIR__ . '/../cas/cassession2.class.php');

/**
 * General answer test which connects to the CAS - prevents duplicate code.
 *
 * @copyright  2012 University of Birmingham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stack_answertest_general_cas extends stack_anstest {

    /**
     * $var bool If this variable is set to true or false we override the
     *      simplification options in the CAS variables.
     */
    private $simp;

    /**
     * @param  string $sans
     * @param  string $tans
     * @param  string $casoption
     */
    public function __construct(stack_ast_container $sans, stack_ast_container $tans, string $atname,
            $atoption = null, $options = null) {
        parent::__construct($sans, $tans, $options, $atoption);

        $this->casfunction       = 'AT'. $atname;
        $this->atname            = $atname;
        $this->simp              = stack_ans_test_controller::simp($atname);
    }

    /**
     *
     *
     * @return bool
     * @access public
     */
    public function do_test() {

        if ('' == trim($this->sanskey->get_inputform())) {
            $this->aterror      = stack_string('TEST_FAILED', array('errors' => stack_string("AT_EmptySA")));
            $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => stack_string("AT_EmptySA")));
            $this->atansnote    = $this->casfunction.'TEST_FAILED:Empty SA.';
            $this->atmark       = 0;
            $this->atvalid      = false;
            return null;
        }

        if ('' == trim($this->tanskey->get_inputform())) {
            $this->aterror      = stack_string('TEST_FAILED', array('errors' => stack_string("AT_EmptyTA")));
            $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => stack_string("AT_EmptyTA")));
            $this->atansnote    = $this->casfunction.'TEST_FAILED:Empty TA.';
            $this->atmark       = 0;
            $this->atvalid      = false;
            return null;
        }

        if (stack_ans_test_controller::process_atoptions($this->atname)) {
            if (null == $this->atoption) {
                $this->aterror      = 'TEST_FAILED';
                $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => stack_string("AT_MissingOptions")));
                $this->atansnote    = 'STACKERROR_OPTION.';
                $this->atmark       = 0;
                $this->atvalid      = false;
                return null;
            }
            if (!$this->atoption->get_valid()) {
                $this->aterror      = 'TEST_FAILED';
                $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => ''));
                $this->atfeedback  .= stack_string('AT_InvalidOptions', array('errors' => $this->atoption->get_errors()));
                $this->atansnote    = 'STACKERROR_OPTION.';
                $this->atmark       = 0;
                $this->atvalid      = false;
                return null;
            }
            if ('' == $this->atoption->get_evaluationform()) {
                $this->aterror      = 'TEST_FAILED';
                $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => stack_string("AT_MissingOptions")));
                $this->atansnote    = 'STACKERROR_OPTION.';
                $this->atmark       = 0;
                $this->atvalid      = false;
                return null;
            }
        }

        // Sort out options.
        if (null === $this->options) {
            $this->options = new stack_options();
        }
        $this->options->set_option('simplify', $this->simp);

        // New values could be based on previously evaluated ones.
        $sa = null;
        if ($this->sanskey->is_correctly_evaluated()) {
            $sa = stack_ast_container::make_from_teacher_source($this->sanskey->get_value());
        } else {
            $sa = clone $this->sanskey;
        }
        $sa->set_nounify(true);
        $sa->set_key('STACKSA');
        $ta = null;
        if ($this->tanskey->is_correctly_evaluated()) {
            $ta = stack_ast_container::make_from_teacher_source($this->tanskey->get_value());
        } else {
            $ta = clone $this->tanskey;
        }
        $ta->set_nounify(true);
        $ta->set_key('STACKTA');
        $ops = stack_ast_container::make_from_teacher_source('STACKOP:true', '', new stack_cas_security());
        $result = stack_ast_container::make_from_teacher_source("result:{$this->casfunction}(STACKSA,STACKTA)", '',
            new stack_cas_security());
        if (stack_ans_test_controller::process_atoptions($this->atname)) {
            if ($this->atoption->is_correctly_evaluated()) {
                $ops = stack_ast_container::make_from_teacher_source($this->atoption->get_value());
            } else {
                $ops = clone $this->atoption;
            }
            $ops->set_key('STACKOP');
            $result = stack_ast_container::make_from_teacher_source("result:{$this->casfunction}(STACKSA,STACKTA,STACKOP)", '',
                new stack_cas_security());
        }
        $session = new stack_cas_session2(array($sa, $ta, $ops, $result), $this->options, 0);
        if ($session->get_valid()) {
            $session->instantiate();
        }
        $this->debuginfo = $session->get_debuginfo();

        if ('' != $sa->get_errors() || !$sa->get_valid()) {
            $this->aterror      = 'TEST_FAILED';
            $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => $sa->get_errors()));
            $this->atansnote    = $this->casfunction.'_STACKERROR_SAns.';
            $this->atmark       = 0;
            $this->atvalid      = false;
            return null;
        }

        if ('' != $ta->get_errors() || !$ta->get_valid()) {
            $this->aterror      = 'TEST_FAILED';
            $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => $ta->get_errors()));
            $this->atansnote    = $this->casfunction.'_STACKERROR_TAns.';
            $this->atmark       = 0;
            $this->atvalid      = false;
            return null;
        }

        if (stack_ans_test_controller::process_atoptions($this->atname)) {
            if ('' != $ops->get_errors() || !$ops->get_valid()) {
                $this->aterror      = 'TEST_FAILED';
                $this->atfeedback   = stack_string('TEST_FAILED', array('errors' => $ops->get_errors()));
                $this->atansnote    = $this->casfunction.'_STACKERROR_Opt.';
                $this->atmark       = 0;
                $this->atvalid      = false;
                return null;
            }
        }

        $unpacked = $this->unpack_result($result->get_evaluated());
        $this->atansnote = str_replace("\n", '', trim($unpacked['answernote']));

        if ('' != $result->get_errors()) {
            $this->aterror      = 'TEST_FAILED';
            if ('' != trim($unpacked['feedback'])) {
                $this->atfeedback = stack_maxima_translate($unpacked['feedback']);
            } else {
                $this->atfeedback = stack_string('TEST_FAILED', array('errors' => $result->get_errors()));
            }
            // Make sure we have a non-empty answer note at least.
            if (!$this->atansnote) {
                $this->atansnote = 'TEST_FAILED';
            }
            $this->atmark       = 0;
            $this->atvalid      = false;
            return null;
        }

        // Convert the Maxima string 'true' to PHP true.
        // Actually in the AST parsing we already have a bool.
        if ($unpacked['result']) {
            $this->atmark = 1;
        } else {
            $this->atmark = 0;
        }
        $this->atfeedback = $unpacked['feedback'];
        $this->atvalid    = $unpacked['valid'];
        if ($this->atmark) {
            return true;
        } else {
            return false;
        }
    }

    private function unpack_result(MP_Node $result): array {
        $r = array('valid' => false, 'result' => 'unknown', 'answernote' => '', 'feedback' => '');

        if ($result instanceof MP_Root) {
            $result = $result->items[0];
        }
        if ($result instanceof MP_Statement) {
            $result = $result->statement;
        }
        if ($result instanceof MP_List) {
            $r['valid'] = $result->items[0]->value;
            $r['result'] = $result->items[1]->value;
            if ($result->items[2] instanceof MP_String) {
                $r['answernote'] = $result->items[2]->value;
            } else if ($result->items[2] instanceof MP_List) {
                // This is an odd case... We really should not have differing types.
                $r['answernote'] = $result->items[2]->toString();
            }
            // Sort out and tidy up any feedback.
            $res = $result->items[3]->value;
            if (strrpos($res, '!NEWLINE!') === core_text::strlen($res) - 9) {
                $res = trim(core_text::substr($res, 0, -9));
            }
            $astc = new stack_ast_container();
            $r['feedback'] = $astc->set_cas_latex_value(stack_maxima_translate($res));
        }
        return $r;
    }

    public function get_debuginfo() {
        return $this->debuginfo;
    }

    /**
     * Validates the options, when needed.
     *
     * @return (bool, string)
     * @access public
     */
    public function validate_atoptions($opt) {
        if (stack_ans_test_controller::process_atoptions($this->atname)) {
            $cs = stack_ast_container::make_from_teacher_source($opt, '', new stack_cas_security());
            return array($cs->get_valid(), $cs->get_errors());
        }
        return array(true, '');
    }
}
