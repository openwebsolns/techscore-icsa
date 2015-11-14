<?php
/*
 * This file is part of TechScore
 *
 * @package rpwriter
 */

require_once('rpwriter/AbstractRpForm.php');
require_once('rpwriter/RpBlock.php');
require_once('rpwriter/LatexPic.php');

/**
 * Draws RP forms for team racing regatta
 *
 * @author Dayan Paez
 * @version 2010-02-08
 */
class IcsaRpFormTeam extends AbstractRpForm {

  const BLOCKS_PER_PAGE = 2;

  protected $HEAD = '\documentclass[letter,12pt]{article} \usepackage{graphicx} \usepackage[text={8.25in,11in},centering]{geometry} \usepackage[usenames]{color} \begin{document} \sffamily\color{blue} \setlength{\unitlength}{1in} \pagestyle{empty}';
  protected $TAIL = '\end{document}';

  public function makePdf(FullRegatta $reg, $tmpbase = 'ts2') {
    $body = sprintf("%s %s %s", 
                    str_replace('#', '\#', $this->HEAD), 
                    str_replace('#', '\#', $this->draw($reg, $this->createBlocks($reg))),
                    str_replace('#', '\#', $this->TAIL));

    // generate PDF
    $tmp = sys_get_temp_dir();
    $filename = tempnam($tmp, $tmpbase);
    $command = sprintf("pdflatex -output-directory='%s' -interaction=nonstopmode -jobname='%s' %s",
                       escapeshellarg($tmp),
                       escapeshellarg(basename($filename)),
                       escapeshellarg($body));
    $output = array();
    exec($command, $output, $value);
    if ($value != 0) {
      throw new RuntimeException(sprintf("Unable to generate PDF file. Exit code $value:\nValue: %s\nOutput%s",
                                         $value, implode("\n", $output)));
    }

    // clean up (including base created by tempnam call (last in list)
    foreach (array('.aux', '.log', '') as $suffix)
      unlink(sprintf('%s%s', $filename, $suffix));
    return sprintf('%s.pdf', $filename);
  }

  protected function createBlocks(FullRegatta $reg) {
    $blocks = array();

    $divisions = $reg->getDivisions();
    $rp = $reg->getRpManager();

    foreach ($reg->getTeams() as $team) {
      $representative = $rp->getRepresentative($team);

      // It may be necessary to use multiple RP blocks per team, due to
      // the fact that the number of skippers or crews exceeds the
      // allowed value for that field per block.
      $team_blocks = array();

      foreach (array(RP::SKIPPER, RP::CREW) as $role) {
        $limit = ($role == RP::SKIPPER) ? 5 : 6;
        $section = $role . '_A';
        foreach ($divisions as $div) {
          foreach ($rp->getRP($team, $div, $role) as $r) {

            // Find block to use
            $block = null;
            foreach ($team_blocks as $bl) {
              if (count($bl->$section) < $limit) {
                $block = $bl;
                break;
              }
            }
            if ($block === null) {
              $block = new RpBlock();
              $block->team = $team;
              $block->representative = $representative;
              $blocks[] = $block;
              $team_blocks[] = $block;
            }

            array_push($block->$section, $r);
          }
        }
      }

      // Add an articifial block if none available for the team
      if (count($team_blocks) == 0) {
        $block = new RpBlock();
        $block->team = $team;
        $block->representative = $representative;
        $blocks[] = $block;
      }
    }

    return $blocks;
  }

  /**
   * Returns the LaTeX code for the body of this form
   *
   * @return String the LaTeX code
   */
  protected function draw(FullRegatta $reg, Array $blocks) {
    $host = array();
    foreach ($reg->getHosts() as $school)
      $host[$school->id] = $school->nick_name;
    $host = implode("/", $host);

    $pics = array();
    $within_page = 0;
    $fmt = '\put(%0.2f, %0.2f){%s}';
    $blocks_per_team = array();
    foreach ($blocks as $block) {
      if ($within_page == 0) {
        $pc = new LatexPic(-0.25, 0);
        $pc->add(sprintf('\put(7.05, 10.33){\thepage} ' .
                         '\put(7.50, 10.33){**num_pages**} ' .
                         '\put(1.75,  9.98){%s} ' .
                         '\put(4.25,  9.98){%s} ' .
                         '\put(6.55,  9.98){%s} ',
                         $reg->name,
                         $host,
                         $reg->start_time->format('Y-m-d')));
        $pics[] = $pc;
      }

      if (!isset($blocks_per_team[$block->team->id]))
        $blocks_per_team[$block->team->id] = 0;
      $blocks_per_team[$block->team->id]++;

      // - team and representative
      $name = sprintf("%s %s", $block->team->school->nick_name, $block->team->name);
      if ($blocks_per_team[$block->team->id] > 1)
        $name .= sprintf(" (%d)", $blocks_per_team[$block->team->id]);
      $team_X = 1.25;
      $team_Y = 9.65 - 4.48 * $within_page;
      $pc->add(sprintf($fmt, $team_X, $team_Y, $name));
      $pc->add(sprintf($fmt, $team_X + 4.6, $team_Y, $block->representative));

      $teamRaces = $reg->getRacesForTeam(Division::A(), $block->team);

      // - write content: skippers across all divisions
      $X = 0.75;
      $Y = 8.65 - 4.48 * $within_page;
      // :A then :B then :C, first column, then second
      $skipIndex = 0;
      foreach ($block->skipper_A as $s) {
        $y = $Y - (0.3 * $skipIndex);
        $skipIndex++;

        $year = substr($s->getSailorYear(), 2);
        if (count($s->races_nums) == count($teamRaces))
          $races = "All";
        else
          $races = DB::makeRange($s->races_nums);
        $pc->add(sprintf($fmt, $X,        $y, $s->getSailorName()));
        $pc->add(sprintf($fmt, $X + 3.0,  $y, $year));
        $pc->add(sprintf($fmt, $X + 3.4, $y, $races));
      }

      // crews
      $X = 0.75;
      $Y = 7.15 - 4.48 * $within_page;
      $crewIndex = 0;
      foreach ($block->crew_A as $s) {
        $y = $Y - (0.3 * $crewIndex);
        $crewIndex++;

        $year = substr($s->getSailorYear(), 2);
        if (count($s->races_nums) == count($teamRaces))
          $races = "All";
        else
          $races = DB::makeRange($s->races_nums);
        $pc->add(sprintf($fmt, $X,        $y, $s->getSailorName()));
        $pc->add(sprintf($fmt, $X + 3.0,  $y, $year));
        $pc->add(sprintf($fmt, $X + 3.4, $y, $races));
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

  protected function getIncludeGraphics() {
    return sprintf('\includegraphics[width=\textwidth]{%s}', $this->getPdfName());
  }

  public function getPdfName() {
    return __DIR__ . '/ICSA-RP-TEAM.pdf';
  }
}
?>