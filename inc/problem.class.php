<?php

/**
 *  -------------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of PDF plugin for GLPI.
 *
 *  PDF is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PDF is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Nelly Mahu-Lasson, Remi Collet, Teclib
 * @copyright Copyright (c) 2009-2022 PDF plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/pdf/
 * @link      http://www.glpi-project.org/
 * @package   pdf
 * @since     2009
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  --------------------------------------------------------------------------
 */

class PluginPdfProblem extends PluginPdfCommon
{
    public static $rightname = 'plugin_pdf';

    public function __construct(CommonGLPI $obj = null)
    {
        $this->obj = ($obj ? $obj : new Problem());
    }

    public static function pdfMain(PluginPdfSimplePDF $pdf, Problem $job)
    {
        $dbu = new DbUtils();

        $ID = $job->getField('id');
        if (!$job->can($ID, READ)) {
            return false;
        }

        $pdf->setColumnsSize(100);

        $pdf->displayTitle('<b>' .
                 (empty($job->fields['name']) ? __('Without title') : $name = $job->fields['name']) . '</b>');

        if (count($_SESSION['glpiactiveentities']) > 1) {
            $entity = ' (' . Dropdown::getDropdownName('glpi_entities', $job->fields['entities_id']) . ')';
        } else {
            $entity = '';
        }

        $pdf->setColumnsSize(50, 50);
        $recipient_name = '';
        if ($job->fields['users_id_recipient']) {
            $recipient = new User();
            $recipient->getFromDB($job->fields['users_id_recipient']);
            $recipient_name = $recipient->getName();
        }

        $sla = $due = $commentsla = '';
        if ($job->fields['time_to_resolve']) {
            $due = '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Time to resolve') . '</b></i>',
                Html::convDateTime($job->fields['time_to_resolve']),
            );
        }
        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Opening date') . '</i></b>',
                Html::convDateTime($job->fields['date']),
            ),
            $due,
        );

        $lastupdate = Html::convDateTime($job->fields['date_mod']);
        if ($job->fields['users_id_lastupdater'] > 0) {
            $lastupdate = sprintf(
                __('%1$s by %2$s'),
                $lastupdate,
                $dbu->getUserName($job->fields['users_id_lastupdater']),
            );
        }

        $pdf->displayLine(
            '<b><i>' . sprintf(__('%1$s: %2$s'), __('By') . '</i></b>', $recipient_name),
            '<b><i>' . sprintf(__('%1$s: %2$s'), __('Last update') . '</i></b>', $lastupdate),
        );

        $status = '';
        if (in_array($job->fields['status'], $job->getSolvedStatusArray())
            || in_array($job->fields['status'], $job->getClosedStatusArray())) {
            $status = sprintf(__('%1$s %2$s'), '-', Html::convDateTime($job->fields['solvedate']));
        }
        if (in_array($job->fields['status'], $job->getClosedStatusArray())) {
            $status = sprintf(__('%1$s %2$s'), '-', Html::convDateTime($job->fields['closedate']));
        }

        if ($job->fields['status'] == Ticket::WAITING) {
            $status = sprintf(
                __('%1$s %2$s'),
                '-',
                Html::convDateTime($job->fields['begin_waiting_date']),
            );
        }

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Status') . '</i></b>',
                Toolbox::stripTags($job->getStatus($job->fields['status'])) . $status,
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Urgency') . '</i></b>',
                Toolbox::stripTags($job->getUrgencyName($job->fields['urgency'])),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Category') . '</i></b>',
                Dropdown::getDropdownName(
                    'glpi_itilcategories',
                    $job->fields['itilcategories_id'],
                ),
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Impact') . '</i></b>',
                Toolbox::stripTags($job->getImpactName($job->fields['impact'])),
            ),
        );

        $pdf->displayLine(
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Total duration') . '</i></b>',
                Toolbox::stripTags(CommonITILObject::getActionTime($job->fields['actiontime'])),
            ),
            '<b><i>' . sprintf(
                __('%1$s: %2$s'),
                __('Priority') . '</i></b>',
                Toolbox::stripTags($job->getPriorityName($job->fields['priority'])),
            ),
        );


        // Requester
        $users     = [];
        $listusers = '';
        $requester = '<b><i>' . sprintf(__('%1$s: %2$s') . '</i></b>', __('Requester'), $listusers);
        foreach ($job->getUsers(CommonITILActor::REQUESTER) as $d) {
            if ($d['users_id']) {
                $tmp = Toolbox::stripTags($dbu->getUserName($d['users_id']));
                if ($d['alternative_email']) {
                    $tmp .= ' (' . $d['alternative_email'] . ')';
                }
            } else {
                $tmp = $d['alternative_email'];
            }
            $users[] = $tmp;
        }
        if (count($users)) {
            $listusers = implode(', ', $users);
        }
        $pdf->displayText($requester, $listusers, 1);

        $groups         = [];
        $listgroups     = '';
        $requestergroup = '<b><i>' . sprintf(
            __('%1$s: %2$s') . '</i></b>',
            __('Requester group'),
            $listgroups,
        );
        foreach ($job->getGroups(CommonITILActor::REQUESTER) as $d) {
            $groups[] = Dropdown::getDropdownName('glpi_groups', $d['groups_id']);
        }
        if (count($groups)) {
            $listgroups = implode(', ', $groups);
        }
        $pdf->displayText($requestergroup, $listgroups, 1);

        // Observer
        $users     = [];
        $listusers = '';
        $watcher   = '<b><i>' . sprintf(__('%1$s: %2$s') . '</i></b>', __('Watcher'), $listusers);
        foreach ($job->getUsers(CommonITILActor::OBSERVER) as $d) {
            if ($d['users_id']) {
                $tmp = Toolbox::stripTags($dbu->getUserName($d['users_id']));
                if ($d['alternative_email']) {
                    $tmp .= ' (' . $d['alternative_email'] . ')';
                }
            } else {
                $tmp = $d['alternative_email'];
            }
            $users[] = $tmp;
        }
        if (count($users)) {
            $listusers = implode(', ', $users);
        }
        $pdf->displayText($watcher, $listusers, 1);

        $groups       = [];
        $listgroups   = '';
        $watchergroup = '<b><i>' . sprintf(
            __('%1$s: %2$s') . '</i></b>',
            __('Watcher group'),
            $listgroups,
        );
        foreach ($job->getGroups(CommonITILActor::OBSERVER) as $d) {
            $groups[] = Dropdown::getDropdownName('glpi_groups', $d['groups_id']);
        }
        if (count($groups)) {
            $listgroups = implode(', ', $groups);
        }
        $pdf->displayText($watchergroup, $listgroups, 1);

        // Assign to
        $users     = [];
        $listusers = '';
        $assign    = '<b><i>' . sprintf(
            __('%1$s: %2$s') . '</i></b>',
            __('Technician as assigned'),
            $listusers,
        );
        foreach ($job->getUsers(CommonITILActor::ASSIGN) as $d) {
            if ($d['users_id']) {
                $tmp = Toolbox::stripTags($dbu->getUserName($d['users_id']));
                if ($d['alternative_email']) {
                    $tmp .= ' (' . $d['alternative_email'] . ')';
                }
            } else {
                $tmp = $d['alternative_email'];
            }
            $users[] = $tmp;
        }
        if (count($users)) {
            $listusers = implode(', ', $users);
        }
        $pdf->displayText($assign, $listusers, 1);

        $groups      = [];
        $listgroups  = '';
        $assigngroup = '<b><i>' . sprintf(
            __('%1$s: %2$s') . '</i></b>',
            __('Technician group'),
            $listgroups,
        );
        foreach ($job->getGroups(CommonITILActor::ASSIGN) as $d) {
            $groups[]
            = Toolbox::stripTags(Glpi\Toolbox\Sanitizer::unsanitize(Dropdown::getDropdownName(
                'glpi_groups',
                $d['groups_id'],
            )));
        }
        if (count($groups)) {
            $listgroups = implode(', ', $groups);
        }
        $pdf->displayText($assigngroup, $listgroups, 1);

        // Supplier
        $suppliers      = [];
        $listsuppliers  = '';
        $assignsupplier = '<b><i>' . sprintf(
            __('%1$s: %2$s') . '</i></b>',
            __('Supplier'),
            $listsuppliers,
        );
        foreach ($job->getSuppliers(CommonITILActor::ASSIGN) as $d) {
            $suppliers[] = Toolbox::stripTags(Dropdown::getDropdownName(
                'glpi_suppliers',
                $d['suppliers_id'],
            ));
        }
        if (count($suppliers)) {
            $listsuppliers = implode(', ', $suppliers);
        }
        $pdf->displayText($assignsupplier, $listsuppliers, 1);

        $pdf->setColumnsSize(100);
        $pdf->displayLine(
            '<b><i>' . sprintf(__('%1$s: %2$s'), __('Title') . '</i></b>', $job->fields['name']),
        );

        $pdf->displayText(
            '<b><i>' . sprintf(__('%1$s: %2$s'), __('Description') . '</i></b>', ''),
            Toolbox::stripTags($job->fields['content']),
            1,
        );

        $pdf->displaySpace();
    }

    public static function pdfForItem(PluginPdfSimplePDF $pdf, CommonDBTM $item, $tree = false)
    {
        global $DB;

        $dbu  = new DbUtils();
        $ID   = $item->getField('id');
        $type = $item->getType();

        if (!Session::haveRight('problem', Problem::READALL)) {
            return;
        }

        switch ($item->getType()) {
            case 'User':
                $restrict = "(`glpi_problems_users`.`users_id` = '" . $item->getID() . "'
                            AND `glpi_problems_users`.`type` = " . CommonITILActor::REQUESTER . ')';
                $order = '`glpi_problems`.`date_mod` DESC';

                // no break
            case 'Supplier':
                $restrict = "(`glpi_problems_suppliers`.`suppliers_id` = '" . $item->getID() . "'
                            AND `glpi_problems_suppliers`.`type` = " . CommonITILActor::REQUESTER . ')';
                $order = '`glpi_problems`.`date_mod` DESC';

                // no break
            case 'Group':
                if ($tree) {
                    $restrict = 'IN (' . implode(',', $dbu->getSonsOf('glpi_groups', $item->getID())) . ')';
                } else {
                    $restrict = "='" . $item->getID() . "'";
                }
                $restrict = "(`glpi_groups_problems`.`groups_id` $restrict
                            AND `glpi_groups_problems`.`type` = " . CommonITILActor::REQUESTER . ')';
                $order = '`glpi_problems`.`date_mod` DESC';

                // no break
            default:
                $restrict = "(`items_id` = '" . $item->getID() . "'  AND `itemtype` = '$type')";
                $order    = '`glpi_problems`.`date_mod` DESC';
        }

        $query = 'SELECT ' . Problem::getCommonSelect() . '
                FROM `glpi_problems`
                LEFT JOIN `glpi_items_problems`
                  ON (`glpi_problems`.`id` = `glpi_items_problems`.`problems_id`) ' .
                  Problem::getCommonLeftJoin() . "
                WHERE $restrict " .
                        $dbu->getEntitiesRestrictRequest('AND', 'glpi_problems') . "
                ORDER BY $order
                LIMIT " . intval($_SESSION['glpilist_limit']);

        $result = $DB->query($query);
        $number = $DB->numrows($result);

        $pdf->setColumnsSize(100);
        if (!$number) {
            $pdf->displayTitle('<b>' . __('No problem found.') . '</b>');
        } else {
            $pdf->displayTitle('<b>' . sprintf(
                _n('Last %d problem', 'Last %d problems', $number),
                $number . '</b>',
            ));

            $job = new Problem();
            while ($data = $DB->fetchAssoc($result)) {
                if (!$job->getFromDB($data['id'])) {
                    continue;
                }
                $pdf->setColumnsAlign('center');
                $col = '<b><i>ID ' . $job->fields['id'] . '</i></b>, ' .
                         sprintf(
                             __('%1$s: %2$s'),
                             __('Status'),
                             Ticket::getStatus($job->fields['status']),
                         );

                if (count($_SESSION['glpiactiveentities']) > 1) {
                    if ($job->fields['entities_id'] == 0) {
                        $col = sprintf(__('%1$s (%2$s)'), $col, __('Root entity'));
                    } else {
                        $col = sprintf(
                            __('%1$s (%2$s)'),
                            $col,
                            Dropdown::getDropdownName(
                                'glpi_entities',
                                -$job->fields['entities_id'],
                            ),
                        );
                    }
                }
                $pdf->displayLine($col);

                $pdf->setColumnsAlign('left');
                $col = '<b><i>' . sprintf(
                    __('Opened on %s') . '</i></b>',
                    Html::convDateTime($job->fields['date']),
                );
                if ($job->fields['begin_waiting_date']) {
                    $col = sprintf(
                        __('%1$s, %2$s'),
                        $col,
                        '<b><i>' . sprintf(
                            __('Put on hold on %s') . '</i></b>',
                            Html::convDateTime($job->fields['begin_waiting_date']),
                        ),
                    );
                }
                if (in_array($job->fields['status'], $job->getSolvedStatusArray())
                    || in_array($job->fields['status'], $job->getClosedStatusArray())) {
                    $col = sprintf(
                        __('%1$s, %2$s'),
                        $col,
                        '<b><i>' . sprintf(
                            __('Solved on %s') . '</i></b>',
                            Html::convDateTime($job->fields['solvedate']),
                        ),
                    );
                }
                if (in_array($job->fields['status'], $job->getClosedStatusArray())) {
                    $col = sprintf(
                        __('%1$s, %2$s'),
                        $col,
                        '<b><i>' . sprintf(
                            __('Closed on %s') . '</i></b>',
                            Html::convDateTime($job->fields['closedate']),
                        ),
                    );
                }
                if ($job->fields['time_to_resolve']) {
                    $col = sprintf(
                        __('%1$s, %2$s'),
                        $col,
                        '<b><i>' . sprintf(
                            __('%1$s: %2$s') . '</i></b>',
                            __('Time to resolve'),
                            Html::convDateTime($job->fields['time_to_resolve']),
                        ),
                    );
                }
                $pdf->displayLine($col);

                $col = '<b><i>' . sprintf(
                    __('%1$s: %2$s'),
                    __('Priority') . '</i></b>',
                    Ticket::getPriorityName($job->fields['priority']),
                );
                if ($job->fields['itilcategories_id']) {
                    $col = sprintf(
                        __('%1$s - %2$s'),
                        $col,
                        '<b><i>' . sprintf(
                            __('%1$s: %2$s') . '</i></b>',
                            __('Category'),
                            Dropdown::getDropdownName(
                                'glpi_itilcategories',
                                $job->fields['itilcategories_id'],
                            ),
                        ),
                    );
                }
                $pdf->displayLine($col);

                $col   = '';
                $users = $job->getUsers(CommonITILActor::REQUESTER);
                if (count($users)) {
                    foreach ($users as $d) {
                        if (empty($col)) {
                            $col = getUserName($d['users_id']);
                        } else {
                            $col = sprintf(__('%1$s, %2$s'), $col, getUserName($d['users_id']));
                        }
                    }
                }
                $grps = $job->getGroups(CommonITILActor::REQUESTER);
                if (count($grps)) {
                    if (empty($col)) {
                        $col = sprintf(__('%1$s %2$s'), $col, _n('Group', 'Groups', 2) . ' </i></b>');
                    } else {
                        $col = sprintf(__('%1$s - %2$s'), $col, _n('Group', 'Groups', 2) . ' </i></b>');
                    }
                    $first = true;
                    foreach ($grps as $d) {
                        if ($first) {
                            $col = sprintf(
                                __('%1$s  %2$s'),
                                $col,
                                Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                            );
                        } else {
                            $col = sprintf(
                                __('%1$s, %2$s'),
                                $col,
                                Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                            );
                        }
                        $first = false;
                    }
                }
                if ($col) {
                    $texte = '<b><i>' . sprintf(__('%1$s: %2$s'), __('Requester') . '</i></b>', '');
                    $pdf->displayText($texte, $col, 1);
                }

                $col   = '';
                $users = $job->getUsers(CommonITILActor::ASSIGN);
                if (count($users)) {
                    foreach ($users as $d) {
                        if (empty($col)) {
                            $col = getUserName($d['users_id']);
                        } else {
                            $col = sprintf(__('%1$s, %2$s'), $col, getUserName($d['users_id']));
                        }
                    }
                }
                $grps = $job->getGroups(CommonITILActor::ASSIGN);
                if (count($grps)) {
                    if (empty($col)) {
                        $col = sprintf(__('%1$s %2$s'), $col, _n('Group', 'Groups', 2) . ' </i></b>');
                    } else {
                        $col = sprintf(__('%1$s - %2$s'), $col, _n('Group', 'Groups', 2) . ' </i></b>');
                    }
                    $first = true;
                    foreach ($grps as $d) {
                        if ($first) {
                            $col = sprintf(
                                __('%1$s  %2$s'),
                                $col,
                                Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                            );
                        } else {
                            $col = sprintf(
                                __('%1$s, %2$s'),
                                $col,
                                Dropdown::getDropdownName('glpi_groups', $d['groups_id']),
                            );
                        }
                        $first = false;
                    }
                }
                if ($col) {
                    $texte = '<b><i>' . sprintf(__('%1$s: %2$s') . '</i></b>', ('Assigned to'), '');
                    $pdf->displayText($texte, $col, 1);
                }

                $texte = '<b><i>' . sprintf(__('%1$s: %2$s') . '</i></b>', ('Title'), '');
                $pdf->displayText($texte, $job->fields['name'], 1);
            }
        }
        $pdf->displaySpace();
    }

    public static function pdfAnalysis(PluginPdfSimplePDF $pdf, Problem $job)
    {
        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . __('Analysis') . '</b>');

        $pdf->setColumnsSize(10, 90);

        $text = '';
        if ($job->fields['impactcontent']) {
            $text = Toolbox::stripTags(Glpi\Toolbox\Sanitizer::unsanitize(
                html_entity_decode(
                    $job->getField('impactcontent'),
                    ENT_QUOTES,
                    'UTF-8',
                ),
            ));
        }
        $pdf->displayText('<b><i>' . sprintf(__('%1$s: %2$s'), __('Impacts') . '</i></b>', $text));

        if ($job->fields['causecontent']) {
            $text = Toolbox::stripTags(Glpi\Toolbox\Sanitizer::unsanitize(
                html_entity_decode(
                    $job->getField('causecontent'),
                    ENT_QUOTES,
                    'UTF-8',
                ),
            ));
        }

        $pdf->displayText('<b><i>' . sprintf(__('%1$s: %2$s'), __('Causes') . '</i></b>', $text));

        if ($job->fields['symptomcontent']) {
            $text = Toolbox::stripTags(Glpi\Toolbox\Sanitizer::unsanitize(
                html_entity_decode(
                    $job->getField('symptomcontent'),
                    ENT_QUOTES,
                    'UTF-8',
                ),
            ));
        }

        $pdf->displayText('<b><i>' . sprintf(__('%1$s: %2$s'), __('Symptoms') . '</i></b>', $text));

        $pdf->displaySpace();
    }

    public static function pdfStat(PluginPdfSimplePDF $pdf, Problem $job)
    {
        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . _n('Date', 'Dates', 2) . '</b>');

        $pdf->setColumnsSize(50, 50);
        $pdf->displayLine(sprintf(
            __('%1$s: %2$s'),
            __('Opening date'),
            Html::convDateTime($job->fields['date']),
        ));
        $pdf->displayLine(sprintf(
            __('%1$s: %2$s'),
            __('Time to resolve'),
            Html::convDateTime($job->fields['time_to_resolve']),
        ));
        if (in_array($job->fields['status'], $job->getSolvedStatusArray())
            || in_array($job->fields['status'], $job->getClosedStatusArray())) {
            $pdf->displayLine(sprintf(
                __('%1$s: %2$s'),
                __('Resolution date'),
                Html::convDateTime($job->fields['solvedate']),
            ));
        }
        if (in_array($job->fields['status'], $job->getClosedStatusArray())) {
            $pdf->displayLine(sprintf(
                __('%1$s: %2$s'),
                __('Closing date'),
                Html::convDateTime($job->fields['closedate']),
            ));
        }

        $pdf->setColumnsSize(100);
        $pdf->displayTitle('<b>' . _n('Time', 'Times', 2) . '</b>');


        if (in_array($job->fields['status'], $job->getSolvedStatusArray())
            || in_array($job->fields['status'], $job->getClosedStatusArray())) {
            if ($job->fields['solve_delay_stat'] > 0) {
                $pdf->displayLine(sprintf(
                    __('%1$s: %2$s'),
                    __('Solution'),
                    Toolbox::stripTags(Html::timestampToString($job->fields['solve_delay_stat'], 0)),
                ));
            }
        }
        if (in_array($job->fields['status'], $job->getClosedStatusArray())) {
            if ($job->fields['close_delay_stat'] > 0) {
                $pdf->displayLine(sprintf(
                    __('%1$s: %2$s'),
                    __('Closing'),
                    Toolbox::stripTags(Html::timestampToString($job->fields['close_delay_stat'], 0)),
                ));
            }
        }
        if ($job->fields['waiting_duration'] > 0) {
            $pdf->displayLine(sprintf(
                __('%1$s: %2$s'),
                __('Pending'),
                Toolbox::stripTags(Html::timestampToString($job->fields['waiting_duration'], 0)),
            ));
        }

        $pdf->displaySpace();
    }

    public function defineAllTabsPDF($options = [])
    {
        $onglets = parent::defineAllTabsPDF($options);
        unset($onglets['Itil_Project$1']);
        unset($onglets['Impact$1']);

        if (Session::haveRight('problem', Problem::READALL) // for technician
            || Session::haveRight('followup', ITILFollowup::SEEPRIVATE)) {
            $onglets['_private_'] = __('Private');
        }

        return $onglets;
    }

    public static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab)
    {
        $private = isset($_REQUEST['item']['_private_']);

        switch ($tab) {
            case '_private_':
                // nothing to export, just a flag
                break;

            case 'Problem$main':
                self::pdfMain($pdf, $item);
                PluginPdfItilFollowup::pdfForItem($pdf, $item, $private);
                PluginPdfProblemTask::pdfForProblem($pdf, $item, $private);
                if (Session::haveRight('document', READ)) {
                    PluginPdfDocument::pdfForItem($pdf, $item);
                }
                PluginPdfITILSolution::pdfForItem($pdf, $item);
                break;

            case 'Problem$1':
                self::pdfAnalysis($pdf, $item);
                break;

            case 'Change_Problem$1':
                PluginPdfChange_Problem::pdfForProblem($pdf, $item);
                break;

            case 'Problem_Ticket$1':
                PluginPdfProblem_Ticket::pdfForProblem($pdf, $item);
                break;

            case 'Problem$5':
                PluginPdfItilFollowup::pdfForItem($pdf, $item, $private);
                PluginPdfProblemTask::pdfForProblem($pdf, $item);
                if (Session::haveRight('document', READ)) {
                    PluginPdfDocument::pdfForItem($pdf, $item);
                }
                PluginPdfITILSolution::pdfForItem($pdf, $item);
                break;

            case 'Item_Problem$1':
                PluginPdfItem_Problem::pdfForProblem($pdf, $item);
                break;

            case 'Problem$4':
                self::pdfStat($pdf, $item);
                break;

            case 'ProblemCost$1':
                PluginPdfCommonItilCost::pdfForItem($pdf, $item);
                break;

            default:
                return false;
        }

        return true;
    }
}
