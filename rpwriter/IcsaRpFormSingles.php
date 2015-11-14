<?php
/*
 * This file is part of TechScore
 *
 * @package rpwriter
 */

require_once('rpwriter/AbstractRpBlockForm.php');

/**
 * Class for writing RP forms for singlehanded events
 *
 * @author Dayan Paez
 * @version 2010-02-08
 */
class IcsaRpFormSingles extends AbstractRpBlockForm {

  const BLOCKS_PER_PAGE = 20;

  protected $num_skipper_A = 1;
  protected $num_crew_A = 0;

  protected $HEAD = '\documentclass[landscape,letter,12pt]{article} \usepackage{graphicx} \usepackage[text={10.5in,8.5in},centering]{geometry} \usepackage[usenames]{color} \begin{document} \sffamily\color{blue}  \setlength{\unitlength}{1in} \pagestyle{empty}';

  /**
   * Returns the LaTeX code for the body of this form
   *
   * @return String the LaTeX code
   */
  protected function draw($regatta_name, $host, $date, Array $blocks) {
    $pics = array();
    $within_page = 0;
    $fmt = '\put(%0.2f, %0.2f){%s}';
    $blocks_per_team = array();
    foreach ($blocks as $block) {
      if ($within_page == 0) {
        $pc = new LatexPic(0.00, 0.05);
        $pc->add(sprintf('\put(9.45, 7.80){\thepage} ' .
                         '\put(9.90, 7.80){**num_pages**} ' .
                         '\put(2.30, 7.45){%s} ' .
                         '\put(5.75, 7.45){%s} ' .
                         '\put(8.75, 7.45){%s} ',
                         $regatta_name,
                         $host,
                         $date));
        $pics[] = $pc;
      }

      if (!isset($blocks_per_team[$block->team->id]))
        $blocks_per_team[$block->team->id] = 0;
      $blocks_per_team[$block->team->id]++;

      // - team and representative
      $name = $block->team->school->nick_name;
      if ($blocks_per_team[$block->team->id] > 1)
        $name .= sprintf(" (%d)", $blocks_per_team[$block->team->id]);
      $team_X = 4.08;
      $team_Y = 6.48 - 0.28 * $within_page;
      $pc->add(sprintf($fmt, $team_X, $team_Y, $name));
      $pc->add(sprintf($fmt, $team_X + 4.1, $team_Y, $block->representative));

      // - write content: skippers
      $y = 6.48 - 0.28 * $within_page;
      // :A
      foreach ($block->skipper_A as $i => $s) {
        $year = substr($s->getSailorYear(), 2);
        $pc->add(sprintf($fmt, 1.35, $y, $s->getSailorName()));
        $pc->add(sprintf($fmt, 5.68, $y, $year));
      }

      // - update within page
      $within_page = ($within_page + 1) % self::BLOCKS_PER_PAGE;
    } // end of blocks

    $inc = $this->getIncludeGraphics();
    $pages = array();
    foreach ($pics as $pic)
      $pages[] = sprintf("%s %s", $inc, $pic);

    $body = implode('\clearpage ', $pages);
    $body = str_replace("**num_pages**", count($pages), $body);
    return str_replace("&", "\&", $body);
  }

  public function getPdfName() {
    return __DIR__ . '/ICSA-RP-SINGLES.pdf';
  }
}
?>