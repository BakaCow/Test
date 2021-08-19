<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Supplier class
 */

class Supplier extends Person
{
	const GOODS_SUPPLIER = 0;
	const COST_SUPPLIER = 1;

	/*
	Determines if a given person_id is a customer
	*/
	public function exists($person_id)
	{
		$builder = $this->db->table('suppliers');	
		$builder->join('people', 'people.person_id = suppliers.person_id');
		$builder->where('suppliers.person_id', $person_id);
		
		return ($builder->get()->getNumRows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$builder = $this->db->table('suppliers');
		$builder->where('deleted', 0);

		return $builder->countAllResults();
	}
	
	/*
	Returns all the suppliers
	*/
	public function get_all($category = self::GOODS_SUPPLIER, $limit_from = 0, $rows = 0)
	{
		$builder = $this->db->table('suppliers');
		$builder->join('people', 'suppliers.person_id = people.person_id');
		$builder->where('category', $category);
		$builder->where('deleted', 0);
		$builder->orderBy('company_name', 'asc');
		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();		
	}
	
	/*
	Gets information about a particular supplier
	*/
	public function get_info($supplier_id)
	{
		$builder = $this->db->table('suppliers');	
		$builder->join('people', 'people.person_id = suppliers.person_id');
		$builder->where('suppliers.person_id', $supplier_id);
		$query = $builder->get();
		
		if($query->getNumRows() == 1)
		{
			return $query->getRow();
		}
		else
		{
			//Get empty base parent object, as $supplier_id is NOT an supplier
			$person_obj = parent::get_info(-1);
			
			//Get all the fields from supplier table		
			//append those fields to base parent object, we we have a complete empty object
			foreach($this->db->list_fields('suppliers') as $field)
			{
				$person_obj->$field = '';
			}
			
			return $person_obj;
		}
	}
	
	/*
	Gets information about multiple suppliers
	*/
	public function get_multiple_info($suppliers_ids)
	{
		$builder = $this->db->table('suppliers');
		$builder->join('people', 'people.person_id = suppliers.person_id');		
		$builder->whereIn('suppliers.person_id', $suppliers_ids);
		$builder->orderBy('last_name', 'asc');

		return $builder->get();
	}
	
	/*
	Inserts or updates a suppliers
	*/
	public function save_supplier(&$person_data, &$supplier_data, $supplier_id = FALSE)
	{
		$success = FALSE;

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->transStart();
		
		if(parent::save($person_data,$supplier_id))
		{
			if(!$supplier_id || !$this->exists($supplier_id))
			{
				$supplier_data['person_id'] = $person_data['person_id'];
				$success = $builder->insert('suppliers', $supplier_data);
			}
			else
			{
				$builder->where('person_id', $supplier_id);
				$success = $builder->update('suppliers', $supplier_data);
			}
		}
		
		$this->db->transComplete();
		
		$success &= $this->db->transStatus();

		return $success;
	}
	
	/*
	Deletes one supplier
	*/
	public function delete($supplier_id)
	{
		$builder->where('person_id', $supplier_id);

		return $builder->update('suppliers', array('deleted' => 1));
	}
	
	/*
	Deletes a list of suppliers
	*/
	public function delete_list($supplier_ids)
	{
		$builder->whereIn('person_id', $supplier_ids);

		return $builder->update('suppliers', array('deleted' => 1));
 	}
 	
 	/*
	Get search suggestions to find suppliers
	*/
	public function get_search_suggestions($search, $unique = FALSE, $limit = 25)
	{
		$suggestions = array();

		$builder = $this->db->table('suppliers');
		$builder->join('people', 'suppliers.person_id = people.person_id');
		$builder->where('deleted', 0);
		$builder->like('company_name', $search);
		$builder->orderBy('company_name', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->company_name);
		}

		$builder = $this->db->table('suppliers');
		$builder->join('people', 'suppliers.person_id = people.person_id');
		$builder->where('deleted', 0);
		$builder->distinct();
		$builder->like('agency_name', $search);
		$builder->where('agency_name IS NOT NULL');
		$builder->orderBy('agency_name', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->agency_name);
		}

		$builder = $this->db->table('suppliers');
		$builder->join('people', 'suppliers.person_id = people.person_id');
		$builder->groupStart();
			$builder->like('first_name', $search);
			$builder->orLike('last_name', $search); 
			$builder->orLike('CONCAT(first_name, " ", last_name)', $search);
		$builder->groupEnd();
		$builder->where('deleted', 0);
		$builder->orderBy('last_name', 'asc');
		foreach($builder->get()->getResult() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->first_name . ' ' . $row->last_name);
		}

		if(!$unique)
		{
			$builder = $this->db->table('suppliers');
			$builder->join('people', 'suppliers.person_id = people.person_id');
			$builder->where('deleted', 0);
			$builder->like('email', $search);
			$builder->orderBy('email', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$builder = $this->db->table('suppliers');
			$builder->join('people', 'suppliers.person_id = people.person_id');
			$builder->where('deleted', 0);
			$builder->like('phone_number', $search);
			$builder->orderBy('phone_number', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}

			$builder = $this->db->table('suppliers');
			$builder->join('people', 'suppliers.person_id = people.person_id');
			$builder->where('deleted', 0);
			$builder->like('account_number', $search);
			$builder->orderBy('account_number', 'asc');
			foreach($builder->get()->getResult() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->account_number);
			}
		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

 	/*
	Gets rows
	*/
	public function get_found_rows($search)
	{
		return $this->search($search, 0, 0, 'last_name', 'asc', TRUE);
	}
	
	/*
	Perform a search on suppliers
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'last_name', $order = 'asc', $count_only = FALSE)
	{
		// get_found_rows case
		if($count_only == TRUE)
		{
			$builder->select('COUNT(suppliers.person_id) as count');
		}

		$builder = $this->db->table('suppliers AS suppliers');
		$builder->join('people', 'suppliers.person_id = people.person_id');
		$builder->groupStart();
			$builder->like('first_name', $search);
			$builder->orLike('last_name', $search);
			$builder->orLike('company_name', $search);
			$builder->orLike('agency_name', $search);
			$builder->orLike('email', $search);
			$builder->orLike('phone_number', $search);
			$builder->orLike('account_number', $search);
			$builder->orLike('CONCAT(first_name, " ", last_name)', $search);
		$builder->groupEnd();
		$builder->where('deleted', 0);
		
		// get_found_rows case
		if($count_only == TRUE)
		{
			return $builder->get()->getRow()->count;
		}

		$builder->orderBy($sort, $order);

		if($rows > 0)
		{
			$builder->limit($rows, $limit_from);
		}

		return $builder->get();
	}

	/*
	Return supplier categories
	*/
	public function get_categories()
	{
		return array(
			self::GOODS_SUPPLIER => lang('Suppliers.goods'),
			self::COST_SUPPLIER => lang('Suppliers.cost')
		);
	}

	/*
	Return a category name given its id
	*/
	public function get_category_name($id)
	{
		if($id == self::GOODS_SUPPLIER)
		{
			return lang('Suppliers.goods');
		}
		elseif($id == self::COST_SUPPLIER)
		{
			return lang('Suppliers.cost');
		}
	}
}
?>
