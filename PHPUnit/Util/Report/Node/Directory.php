<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2007, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.2.0
 */

require_once 'PHPUnit/Util/Filter.php';
require_once 'PHPUnit/Util/Filesystem.php';
require_once 'PHPUnit/Util/Template.php';
require_once 'PHPUnit/Util/Report/Node.php';
require_once 'PHPUnit/Util/Report/Node/File.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * Represents a directory in the code coverage information tree.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 3.2.0
 */
class PHPUnit_Util_Report_Node_Directory extends PHPUnit_Util_Report_Node
{
    const LOW_UPPER_BOUND  = 35;
    const HIGH_LOWER_BOUND = 70;

    /**
     * @var    PHPUnit_Util_Report_Node[]
     * @access protected
     */
    protected $children = array();

    /**
     * @var    PHPUnit_Util_Report_Node_Directory[]
     * @access protected
     */
    protected $directories = array();

    /**
     * @var    PHPUnit_Util_Report_Node_File[]
     * @access protected
     */
    protected $files = array();

    /**
     * @var    array
     * @access protected
     */
    protected $classes;

    /**
     * @var    integer
     * @access protected
     */
    protected $numExecutableLines = -1;

    /**
     * @var    integer
     * @access protected
     */
    protected $numExecutedLines = -1;

    /**
     * @var    integer
     * @access protected
     */
    protected $numClasses = -1;

    /**
     * @var    integer
     * @access protected
     */
    protected $numCalledClasses = -1;

    /**
     * @var    integer
     * @access protected
     */
    protected $numMethods = -1;

    /**
     * @var    integer
     * @access protected
     */
    protected $numCalledMethods = -1;

    /**
     * Adds a new directory.
     *
     * @return PHPUnit_Util_Report_Node_Directory
     * @access public
     */
    public function addDirectory($name)
    {
        $directory = new PHPUnit_Util_Report_Node_Directory(
          $name,
          $this,
          $this->highlight
        );

        $this->children[]    = $directory;
        $this->directories[] = &$this->children[count($this->children) - 1];

        return $directory;
    }

    /**
     * Adds a new file.
     *
     * @param  string $name
     * @param  array  $lines
     * @return PHPUnit_Util_Report_Node_File
     * @throws RuntimeException
     * @access public
     */
    public function addFile($name, array $lines)
    {
        $file = new PHPUnit_Util_Report_Node_File(
          $name,
          $this,
          $this->highlight,
          $lines
        );

        $this->children[] = $file;
        $this->files[]    = &$this->children[count($this->children) - 1];

        $this->numExecutableLines = -1;
        $this->numExecutedLines   = -1;

        return $file;
    }

    /**
     * Returns the directories in this directory.
     *
     * @return
     * @access public
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * Returns the files in this directory.
     *
     * @return
     * @access public
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Returns the classes of this node.
     *
     * @return array
     * @access public
     */
    public function getClasses()
    {
        if ($this->classes === NULL) {
            $this->classes = array();

            foreach ($this->children as $child) {
                $this->classes = array_merge($this->classes, $child->getClasses());
            }
        }

        return $this->classes;
    }

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     * @access public
     */
    public function getNumExecutableLines()
    {
        if ($this->numExecutableLines == -1) {
            $this->numExecutableLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutableLines += $child->getNumExecutableLines();
            }
        }

        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     * @access public
     */
    public function getNumExecutedLines()
    {
        if ($this->numExecutedLines == -1) {
            $this->numExecutedLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLines += $child->getNumExecutedLines();
            }
        }

        return $this->numExecutedLines;
    }

    /**
     * Returns the number of classes.
     *
     * @return integer
     * @access public
     */
    public function getNumClasses()
    {
        if ($this->numClasses == -1) {
            $this->numClasses = 0;

            foreach ($this->children as $child) {
                $this->numClasses += $child->getNumClasses();
            }
        }

        return $this->numClasses;
    }

    /**
     * Returns the number of classes of which at least one method
     * has been called at least once.
     *
     * @return integer
     * @access public
     */
    public function getNumCalledClasses()
    {
        if ($this->numCalledClasses == -1) {
            $this->numCalledClasses = 0;

            foreach ($this->children as $child) {
                $this->numCalledClasses += $child->getNumCalledClasses();
            }
        }

        return $this->numCalledClasses;
    }

    /**
     * Returns the number of methods.
     *
     * @return integer
     * @access public
     */
    public function getNumMethods()
    {
        if ($this->numMethods == -1) {
            $this->numMethods = 0;

            foreach ($this->children as $child) {
                $this->numMethods += $child->getNumMethods();
            }
        }

        return $this->numMethods;
    }

    /**
     * Returns the number of methods that has been called at least once.
     *
     * @return integer
     * @access public
     */
    public function getNumCalledMethods()
    {
        if ($this->numCalledMethods == -1) {
            $this->numCalledMethods = 0;

            foreach ($this->children as $child) {
                $this->numCalledMethods += $child->getNumCalledMethods();
            }
        }

        return $this->numCalledMethods;
    }

    /**
     * Renders this node.
     *
     * @param string $target
     * @param string $title
     * @param string $charset
     * @access public
     */
    public function render($target, $title, $charset = 'ISO-8859-1')
    {
        $this->doRender($target, $title, $charset);

        foreach ($this->children as $child) {
            $child->render($target, $title, $charset);
        }
    }

    /**
     * @param  string   $target
     * @param  string   $title
     * @param  string   $charset
     * @access protected
     */
    protected function doRender($target, $title, $charset)
    {
        $cleanId = PHPUnit_Util_Filesystem::getSafeFilename($this->getId());
        $file    = $target . $cleanId . '.html';

        $template = new PHPUnit_Util_Template(
          PHPUnit_Util_Report::$templatePath . 'coverage_directory.html'
        );

        $this->setTemplateVars($template, $title, $charset);

        $totalClassesPercent = $this->getCalledClassesPercent();

        list($totalClassesColor, $totalClassesLevel) = $this->getColorLevel(
          $totalClassesPercent
        );

        $totalMethodsPercent = $this->getCalledMethodsPercent();

        list($totalMethodsColor, $totalMethodsLevel) = $this->getColorLevel(
          $totalMethodsPercent
        );

        $totalLinesPercent = $this->getLineExecutedPercent();

        list($totalLinesColor, $totalLinesLevel) = $this->getColorLevel(
          $totalLinesPercent
        );

        $template->setVar(
          array(
            'total_classes_color',
            'total_classes_level',
            'total_classes_called_width',
            'total_classes_called_percent',
            'total_classes_not_called_width',
            'total_num_called_classes',
            'total_num_classes',

            'total_methods_color',
            'total_methods_level',
            'total_methods_called_width',
            'total_methods_called_percent',
            'total_methods_not_called_width',
            'total_num_called_methods',
            'total_num_methods',

            'total_lines_executed_color',
            'total_lines_executed_level',
            'total_lines_executed_width',
            'total_lines_executed_percent',
            'total_lines_not_executed_width',

            'items',
            'low_upper_bound',
            'high_lower_bound'
          ),
          array(
            $totalClassesColor,
            $totalClassesLevel,
            floor($totalClassesPercent),
            $totalClassesPercent,
            100 - floor($totalClassesPercent),
            $this->getNumCalledClasses(),
            $this->getNumClasses(),

            $totalMethodsColor,
            $totalMethodsLevel,
            floor($totalMethodsPercent),
            $totalMethodsPercent,
            100 - floor($totalMethodsPercent),
            $this->getNumCalledMethods(),
            $this->getNumMethods(),

            $totalLinesColor,
            $totalLinesLevel,
            floor($totalLinesPercent),
            $totalLinesPercent,
            100 - floor($totalLinesPercent),

            $this->renderItems(),
            self::LOW_UPPER_BOUND,
            self::HIGH_LOWER_BOUND
          )
        );

        $template->renderTo($file);
    }

    /**
     * @return string
     * @access protected
     */
    protected function renderItems()
    {
        $items  = $this->doRenderItems($this->directories);
        $items .= $this->doRenderItems($this->files);

        return $items;
    }

    /**
     * @param  array    $items
     * @return string
     * @access protected
     */
    protected function doRenderItems(array $items)
    {
        $result = '';

        foreach ($items as $item) {
            $itemTemplate = new PHPUnit_Util_Template(
              PHPUnit_Util_Report::$templatePath . 'coverage_item.html'
            );

            list($color, $level) = $this->getColorLevel(
              $item->getLineExecutedPercent()
            );

            $calledClassesPercent = $item->getCalledClassesPercent();
            $calledMethodsPercent = $item->getCalledMethodsPercent();
            $executedLinesPercent = $item->getLineExecutedPercent();

            $itemTemplate->setVar(
              array(
                'link',
                'color',
                'level',
                'classes_called_width',
                'classes_called_percent',
                'classes_not_called_width',
                'num_classes',
                'num_called_classes',
                'methods_called_width',
                'methods_called_percent',
                'methods_not_called_width',
                'num_methods',
                'num_called_methods',
                'lines_executed_width',
                'lines_executed_percent',
                'lines_not_executed_width',
                'num_executable_lines',
                'num_executed_lines'
              ),
              array(
                $item->getLink(FALSE),
                $color,
                $level,
                floor($calledClassesPercent),
                $calledClassesPercent,
                100 - floor($calledClassesPercent),
                $item->getNumClasses(),
                $item->getNumCalledClasses(),
                floor($calledMethodsPercent),
                $calledMethodsPercent,
                100 - floor($calledMethodsPercent),
                $item->getNumMethods(),
                $item->getNumCalledMethods(),
                floor($executedLinesPercent),
                $executedLinesPercent,
                100 - floor($executedLinesPercent),
                $item->getNumExecutableLines(),
                $item->getNumExecutedLines()
              )
            );

            $result .= $itemTemplate->render();
        }

        return $result;
    }

    protected function getColorLevel($percent)
    {
        $floorPercent = floor($percent);

        if ($floorPercent < self::LOW_UPPER_BOUND) {
            $color = 'scarlet_red';
            $level = 'Lo';
        }

        else if ($floorPercent >= self::LOW_UPPER_BOUND &&
                 $floorPercent <  self::HIGH_LOWER_BOUND) {
            $color = 'butter';
            $level = 'Med';
        }

        else {
            $color = 'chameleon';
            $level = 'Hi';
        }

        return array($color, $level);
    }
}
?>
