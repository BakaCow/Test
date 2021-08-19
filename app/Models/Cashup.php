<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Cashup class
 */

class Cashup extends Model
{
	/*
	Determines if a given Cashup_id is an Cashup
	*/
	public function exists($cashup_id)
	{
		$builder = $this->db->table('cash_up');
		$builder->where('cashup_id', $cashup_id);

		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets employee info
	*/
	public function get_employee($cashup_id)
	{
		$builder = $this->db->table('cash_up');
		$builder->where('cashup_id', $cashup_id);

		return $this->Employee->get_info($builder->get()->getRow()->employee_id);
	}

	public function get_multiple_info($cash_up_ids)
	{
		$builder = $this->db->table('cash_up');
		$builder->whereIn('cashup_id', $cashup_ids);
		$builder->orderBy('cashup_id', 'asc');

		return $builder->get();
	}

	/*
	Gets rows
	*/
	public function get_found_rows($search, $filters)
	{
		return $this->search($search, $filters, 0, 0, 'cashup_id', 'asc', TRUE);
	}

	/*
	Searches cashups
	*/
	public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'cashup_id', $order = 'asc', $count_only = FALSE)
	{
		// get_found_rows case
		if($count_only == TRUE)
		{
			$builder->select('COUNT(cash_up.cashup_id) as count');
		}

		$builder->select('
			cash_up.cashup_id,
			MAX(cash_up.open_date) AS open_date,
			MAX(cash_up.close_date) AS close_date,
			MAX(cash_up.open_amount_cash) AS open_amount_cash,
			MAX(cash_up.transfer_amount_cash) AS transfer_amount_cash,
			MAX(cash_up.closed_amount_cash) AS closed_amount_cash,
			MAX(cash_up.closed_amount_due) AS closed_amount_due,
			MAX(cash_up.closed_amount_card) AS closed_amount_card,
			MAX(cash_up.closed_amount_check) AS closed_amount_check,
			MAX(cash_up.closed_amount_total) AS closed_amount_total,
			MAX(cash_up.description) AS description,
			MAX(cash_up.note) AS note,
			MAX(cash_up.open_employee_id) AS open_employee_id,
			MAX(cash_up.close_employee_id) AS close_employee_id,
			MAX(open_employees.first_name) AS open_first_name,
			MAX(open_employees.last_name) AS open_last_name,
			MAX(close_employees.first_name) AS close_first_name,
			MAX(close_employees.last_name) AS close_last_name
		');
		$builder = $this->db->table('cash_up AS cash_up');
		$builder->join('people AS open_employees', 'open_employees.person_id = cash_up.open_employee_id', 'LEFT');
		$builder->join('people AS close_employees', 'close_employees.person_id = cash_up.close_employee_id', 'LEFT');

		$builder->groupStart();
			$builder->like('cash_up.open_date', $search);
			$builder->orLike('open_employees.first_name', $search);
			$builder->orLike('open_employees.last_name', $search);
			$builder->orLike('close_employees.first_name', $search);
			$builder->orLike('close_employees.last_name', $search);
			$builder->orLike('cash_up.closed_amount_total', $search);
			$builder->orLike('CONCAT(open_employees.first_name, " ", open_employees.last_name)', $search);
			$builder->orLike('CONCAT(close_employees.first_name, " ", close_employees.last_name)', $search);
		$builder->groupEnd();

		$builder->where('cash_up.deleted', $filters['is_deleted']);

		if(empty($this->config->item('date_or_time_format')))
		{
			$builder->where('DATE_FORMAT(cash_up.open_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
		}
		else
		{
			$builder->where('cash_up.open_date BETWEEN ' . $this->db->escape(rawurldecode($filters['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($filters['end_date'])));
		}

		$this->db->group_by('cashup_id');

		// get_found_rows case
		if($count_only == TRUE)
		{
			return $builder->get()->row_array()['count'];
		}

		$builder->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}

	/*
	Gets information about a particular cashup
	*/
	public function get_info($cashup_id)
	{
		$builder->select('
			cash_up.cashup_id AS cashup_id,
			cash_up.open_date AS open_date,
			cash_up.close_date AS close_date,
			cash_up.open_amount_cash AS open_amount_cash,
			cash_up.transfer_amount_cash AS transfer_amount_cash,
			cash_up.closed_amount_cash AS closed_amount_cash,
			cash_up.closed_amount_due AS closed_amount_due,
			cash_up.closed_amount_card AS closed_amount_card,
			cash_up.closed_amount_check AS closed_amount_check,
			cash_up.closed_amount_total AS closed_amount_total,
			cash_up.description AS description,
			cash_up.note AS note,
			cash_up.open_employee_id AS open_employee_id,
			cash_up.close_employee_id AS close_employee_id,
			cash_up.deleted AS deleted,
			open_employees.first_name AS open_first_name,
			open_employees.last_name AS open_last_name,
			close_employees.first_name AS close_first_name,
			close_employees.last_name AS close_last_name
		');
		$builder = $this->db->table('cash_up AS cash_up');
		$builder->join('people AS open_employees', 'open_employees.person_id = cash_up.open_employee_id', 'LEFT');
		$builder->join('people AS close_employees', 'close_employees.person_id = cash_up.close_employee_id', 'LEFT');
		$builder->where('cashup_id', $cashup_id);

		$query = $builder->get();
		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object
			$cash_up_obj = new stdClass();

			//Get all the fields from cashup table
			foreach($this->db->list_fields('cash_up') as $field)
			{
				$cash_up_obj->$field = '';
			}

			return $cash_up_obj;
		}
	}

	/*
	Inserts or updates an cashup
	*/
	public function save(&$cash_up_data, $cashup_id = FALSE)
	{
		if(!$cashup_id == -1 || !$this->exists($cashup_id))
		{
			if($builder->insert('cash_up', $cash_up_data))
			{
				$cash_up_data['cashup_id'] = $this->db->insertID();

				return TRUE;
			}

			return FALSE;
		}

		$builder->where('cashup_id', $cashup_id);

		return $builder->update('cash_up', $cash_up_data);
	}

	/*
	Deletes a list of cashups
	*/
	public function delete_list($cashup_ids)
	{
		$success = FALSE;

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
			$builder->whereIn('cashup_id', $cashup_ids);
			$success = $builder->update('cash_up', array('deleted'=>1));
		$this->db->transComplete();

		return $success;
	}
}
?>
