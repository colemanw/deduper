<?php


namespace Civi\Api4\Action\Name;

use Civi;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use TheIconic\NameParser\Parser;

/**
 * Class Parse.
 *
 * Parse a name into component parts.
 *
 * @method $this setNames(array $names) Set names to parse.
 * @method array getNames() Get names to parse.
 */
class Parse extends AbstractAction {

  /**
   * Names to parse.
   *
   * @var array
   */
  protected $names = [];

  /**
   * @inheritDoc
   *
   * @param \Civi\Api4\Generic\Result $result
   *
   */
  public function _run(Result $result): void {
    foreach ($this->getNames() as $name) {
      $splitNames = explode(' & ', $name);
      $doubleSplitNames = [];
      foreach ($splitNames as $splitName) {
        $extraSplit = explode(' and ', $splitName);
        foreach ($extraSplit as $toUse) {
          $doubleSplitNames[] = trim($toUse);
        }
      }
      $result[$name] = $this->parseName($doubleSplitNames[0]);
      if (!empty($doubleSplitNames[1])) {
        $result[$name]['Partner.Partner'] = $doubleSplitNames[1];
        if (empty($result[$name]['last_name'])) {
          $result[$name]['last_name'] = $this->parseName($doubleSplitNames[1])['last_name'];
        }
      }
    }
  }

  /**
   * Parse the name into component parts.
   *
   * @param string $name
   *
   * @return array
   */
  protected function parseName(string $name): array {
    $parser = new Parser();
    $nameParser = $parser->parse($name);
    return [
      'prefix_id:label' => $nameParser->getSalutation(),
      'first_name' => $nameParser->getFirstname(),
      'last_name' => $nameParser->getLastname(),
      'middle_name' => $nameParser->getMiddlename(),
      'nick_name' => $nameParser->getNickName(),
      'initials' => $nameParser->getInitials(),
      'suffix_id:label' => $nameParser->getSuffix(),
    ];
  }

}
