<?php

namespace App\Controllers;

class Tax_codes extends Secure_Controller
{
	public function __construct()
	{
		parent::__construct('tax_codes');
	}


	public function index()
	{
		 echo view('taxes/tax_codes',get_data());
	}

	public function get_data()
	{
		$data['table_headers'] = $this->xss_clean(get_tax_codes_table_headers());
		return $data;
	}

	/*
	 * Returns tax_category table data rows. This will be called with AJAX.
	 */
	public function search()
	{
		$search = $this->request->getGet('search');
		$limit  = $this->request->getGet('limit');
		$offset = $this->request->getGet('offset');
		$sort   = $this->request->getGet('sort');
		$order  = $this->request->getGet('order');

		$tax_codes = $this->Tax_code->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Tax_code->get_found_rows($search);

		$data_rows = [];
		foreach($tax_codes->getResult() as $tax_code)
		{
			$data_rows[] = $this->xss_clean(get_tax_code_data_row($tax_code));
		}

		echo json_encode (['total' => $total_rows, 'rows' => $data_rows));
	}

	public function get_row($row_id)
	{
		$data_row = $this->xss_clean(get_tax_code_data_row($this->Tax_code->get_info($row_id)));

		echo json_encode($data_row);
	}

	public function view($tax_code_id = -1)
	{
		$data['tax_code_info'] = $this->Tax_code->get_info($tax_code_id);

		echo view("taxes/tax_code_form", $data);
	}


	public function save($tax_code_id = -1)
	{
		$tax_code_data = [
			'tax_code' => $this->request->getPost('tax_code'),
			'tax_code_name' => $this->request->getPost('tax_code_name'),
			'city' => $this->request->getPost('city'),
			'state' => $this->request->getPost('state')
		);

		if($this->Tax_code->save($tax_code_data))
		{
			$tax_code_data = $this->xss_clean($tax_code_data);

			if($tax_code_id == -1)
			{
				echo json_encode (['success' => TRUE, 'message' => lang('Tax_codes.successful_adding'), 'id' => $tax_code_data['tax_code_id']));
			}
			else
			{
				echo json_encode (['success' => TRUE, 'message' => lang('Tax_codes.successful_updating'), 'id' => $tax_code_id));
			}
		}
		else
		{
			echo json_encode (['success' => FALSE, 'message' => lang('Tax_codes.error_adding_updating') . ' ' . $tax_code_data['tax_code_id'], 'id' => -1));
		}
	}

	public function delete()
	{
		$tax_codes_to_delete = $this->request->getPost('ids');

		if($this->Tax_code->delete_list($tax_codes_to_delete))
		{
			echo json_encode (['success' => TRUE, 'message' => lang('Tax_codes.successful_deleted') . ' ' . count($tax_codes_to_delete) . ' ' . lang('Tax_codes.one_or_multiple')));
		}
		else
		{
			echo json_encode (['success' => FALSE, 'message' => lang('Tax_codes.cannot_be_deleted')));
		}
	}
}
?>
