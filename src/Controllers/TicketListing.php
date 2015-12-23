<?php
/*!
 * Traq
 * Copyright (C) 2009-2015 Jack P.
 * Copyright (C) 2012-2015 Traq.io
 * https://github.com/nirix
 * https://traq.io
 *
 * This file is part of Traq.
 *
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 *
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Traq\Controllers;

use Avalon\Http\Request;
use Avalon\Helpers\Pagination;
use Traq\Helpers\Ticketlist;
use Traq\Helpers\TicketFilterQuery;
use Traq\Models\CustomField;

/**
 * Ticket listing controller.
 *
 * @author Jack P.
 * @since 4.0.0
 */
class TicketListing extends AppController
{
    public function __construct()
    {
        parent::__construct();
        $this->title($this->translate('tickets'));

        // Custom fields
        $this->customFields = CustomField::forProject($this->currentProject['id']);
        $this->set('customFields', $this->customFields);
    }

    /**
     * Ticket listing.
     */
    public function indexAction()
    {
        // Only get the current projects tickets
        $tickets = ticketQuery()
            ->where('t.project_id = ?')
            ->setParameter(0, $this->currentProject['id']);

        // Sort tickets by the projects sorting setting or by the users selection
        $this->sortTickets($tickets);

        // Filter tickets
        $filter = new TicketFilterQuery($tickets, $this->getFilters());
        $queryString = $filter->query;

        // Paginate tickets
        $pagination = new Pagination(
            Request::$query->get('page', 1),
            setting('tickets_per_page'),
            $tickets->execute()->rowCount(),
            $filter->query
        );

        if ($pagination->paginate) {
            $tickets->setFirstResult($pagination->limit);
            $tickets->setMaxResults(setting('tickets_per_page'));
        }

        // Fetch all tickets
        $tickets = $tickets->execute()->fetchAll();

        return $this->render('ticket_listing/index.phtml', [
            'columns'     => $this->getColumns(),
            'tickets'     => $tickets,
            'pagination'  => $pagination
        ]);
    }

    /**
     * Sort tickets.
     */
    protected function sortTickets($tickets)
    {
        $sorting = explode('.', $this->currentProject['default_ticket_sorting']);

        if ($sorting[0] == 'priority') {
            $sortColumn = 'priority_id';
        } elseif ($sorting[0] == 'ticket_id') {
            $sortColumn = 'ticket_id';
        }

        $tickets->orderBy("t.{$sortColumn}, t.ticket_id", $sorting[1]);
    }

    protected function getFilters()
    {
        $allowedFilters = [
            'open',
            'started',
            'closed',
            'milestone'
        ];

        $query = [];
        foreach ($allowedFilters as $filter) {
            if (Request::$query->has($filter)) {
                $query[$filter] = Request::$query->get($filter);
            }
        }

        if (!count($query) && isset($_SESSION['ticketFilters'])) {
            $query = json_decode($_SESSION['ticketFilters'], true);
        }

        return $query;
    }

    public function setColumnsAction()
    {
        $this->getColumns();
        return $this->redirect(routeUrl('tickets', ['pslug' => $this->currentProject['slug']]) . '?' . $_SERVER['QUERY_STRING']);
    }

    protected function getColumns()
    {
        $allowedColumns = Ticketlist::allowedColumns();

        // Add custom fields
        foreach ($this->customFields as $field) {
            $allowedColumns[] = $field->id;
        }

        if (Request::$method == 'POST' && Request::$post->has('update_columns')) {
            // Columns from POST
            $newColumns = [];

            foreach (Request::$post->get('columns', [], false) as $column) {
                $newColumns[] = $column;
            }

            $_SESSION['columns'] = Request::$post['columns'] = $newColumns;
            return $newColumns;
        } elseif (isset(Request::$query['columns'])) {
            // Columns from request
            $columns = [];

            foreach (explode(',', Request::$post->get('columns', [], false)) as $column) {
                // Make sure it's a valid column
                if (in_array($column, $allowedColumns)) {
                    $columns[] = $column;
                }
            }

            return $columns;
        } elseif (isset($_SESSION['columns'])) {
            // Columns from session
            return $_SESSION['columns'];
        } else {
            // Use default columns
            return Ticketlist::defaultColumns();
        }
    }
}
