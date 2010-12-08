<?php

class Report extends Controller {
	var $acc_array;
	var $account_counter;
	function Report()
	{
		parent::Controller();
		$this->load->model('Ledger_model');
		return;
	}
	
	function index()
	{
		$this->template->set('page_title', 'Reports');
		$this->template->load('template', 'report/index');
		return;
	}

	function balancesheet($period = NULL)
	{
		$this->template->set('page_title', 'Balance Sheet');
		$this->template->load('template', 'report/balancesheet');
		return;
	}

	function profitandloss($period = NULL)
	{
		$this->template->set('page_title', 'Profit And Loss Statement');
		$this->template->load('template', 'report/profitandloss');
		return;
	}

	function trialbalance($period = NULL)
	{
		$this->template->set('page_title', 'Trial Balance');
		$this->template->set('nav_links', array('report/download/trialbalance' => 'Download CSV'));

		$this->load->library('accountlist');
		$this->template->load('template', 'report/trialbalance');
		return;
	}

	function ledgerst($ledger_id = 0)
	{
		/* Pagination setup */
		$this->load->library('pagination');

		$this->template->set('page_title', 'Ledger Statement');
		if ($ledger_id != 0)
			$this->template->set('nav_links', array('report/download/ledgerst/' . $ledger_id  => 'Download CSV'));

		if ($_POST)
		{
			$ledger_id = $this->input->post('ledger_id', TRUE);
			redirect('report/ledgerst/' . $ledger_id);
		}
		$data['ledger_id'] = $ledger_id;
		$this->template->load('template', 'report/ledgerst', $data);
		return;
	}

	function download($statement, $id = NULL)
	{
		/********************** TRIAL BALANCE *************************/
		if ($statement == "trialbalance")
		{
			$this->load->model('Ledger_model');
			$all_ledgers = $this->Ledger_model->get_all_ledgers();
			$counter = 0;
			$trialbalance = array();
			$temp_dr_total = 0;
			$temp_cr_total = 0;

			$trialbalance[$counter] = array ("TRIAL BALANCE", "", "", "", "", "", "", "", "");
			$counter++;
			$trialbalance[$counter] = array ("AY " . date_mysql_to_php($this->config->item('account_ay_start')) . " - " . date_mysql_to_php($this->config->item('account_ay_end')), "", "", "", "", "", "", "", "");
			$counter++;

			$trialbalance[$counter][0]= "Ledger";
			$trialbalance[$counter][1]= "";
			$trialbalance[$counter][2]= "Opening";
			$trialbalance[$counter][3]= "";
			$trialbalance[$counter][4]= "Closing";
			$trialbalance[$counter][5]= "";
			$trialbalance[$counter][6]= "Dr Total";
			$trialbalance[$counter][7]= "";
			$trialbalance[$counter][8]= "Cr Total";
			$counter++;

			foreach ($all_ledgers as $ledger_id => $ledger_name)
			{
				if ($ledger_id == 0) continue;

				$trialbalance[$counter][0] = $ledger_name;

				list ($opbal_amount, $opbal_type) = $this->Ledger_model->get_op_balance($ledger_id);
				if ($opbal_amount == 0)
				{
					$trialbalance[$counter][1] = "";
					$trialbalance[$counter][2] = 0;
				} else {
					$trialbalance[$counter][1] = convert_dc($opbal_type);
					$trialbalance[$counter][2] = $opbal_amount;
				}

				$clbal_amount = $this->Ledger_model->get_ledger_balance($ledger_id);

				if ($clbal_amount == 0)
				{
					$trialbalance[$counter][3] = "";
					$trialbalance[$counter][4] = 0;
				} else if ($clbal_amount < 0) {
					$trialbalance[$counter][3] = "Cr";
					$trialbalance[$counter][4] = convert_cur(-$clbal_amount);
				} else {
					$trialbalance[$counter][3] = "Dr";
					$trialbalance[$counter][4] = convert_cur($clbal_amount);
				}

				$dr_total = $this->Ledger_model->get_dr_total($ledger_id);
				if ($dr_total)
				{
					$trialbalance[$counter][5] = "Dr";
					$trialbalance[$counter][6] = convert_cur($dr_total);
					$temp_dr_total += $dr_total;
				} else {
					$trialbalance[$counter][5] = "";
					$trialbalance[$counter][6] = 0;
				}

				$cr_total = $this->Ledger_model->get_cr_total($ledger_id);
				if ($cr_total)
				{
					$trialbalance[$counter][7] = "Cr";
					$trialbalance[$counter][8] = convert_cur($cr_total);
					$temp_cr_total += $cr_total;
				} else {
					$trialbalance[$counter][7] = "";
					$trialbalance[$counter][8] = 0;
				}
				$counter++;
			}

			$trialbalance[$counter][0]= "";
			$trialbalance[$counter][1]= "";
			$trialbalance[$counter][2]= "";
			$trialbalance[$counter][3]= "";
			$trialbalance[$counter][4]= "";
			$trialbalance[$counter][5]= "";
			$trialbalance[$counter][6]= "";
			$trialbalance[$counter][7]= "";
			$trialbalance[$counter][8]= "";
			$counter++;

			$trialbalance[$counter][0]= "Total";
			$trialbalance[$counter][1]= "";
			$trialbalance[$counter][2]= "";
			$trialbalance[$counter][3]= "";
			$trialbalance[$counter][4]= "";
			$trialbalance[$counter][5]= "Dr";
			$trialbalance[$counter][6]= convert_cur($temp_dr_total);
			$trialbalance[$counter][7]= "Cr";
			$trialbalance[$counter][8]= convert_cur($temp_cr_total);

			$this->load->helper('csv');
			echo array_to_csv($trialbalance, "trialbalance.csv");
			return;
		}

		/********************** LEDGER STATEMENT **********************/
		if ($statement == "ledgerst")
		{
			$ledger_id = (int)$this->uri->segment(4);
			if ($ledger_id < 1)
				return;

			$this->load->model('Ledger_model');
			$cur_balance = 0;
			$counter = 0;
			$ledgerst = array();

			$ledgerst[$counter] = array ("", "", "LEDGER STATEMENT FOR " . strtoupper($this->Ledger_model->get_name($ledger_id)), "", "", "", "", "", "", "", "");
			$counter++;
			$ledgerst[$counter] = array ("", "", "AY " . date_mysql_to_php($this->config->item('account_ay_start')) . " - " . date_mysql_to_php($this->config->item('account_ay_end')), "", "", "", "", "", "", "", "");
			$counter++;

			$ledgerst[$counter][0]= "Date";
			$ledgerst[$counter][1]= "Number";
			$ledgerst[$counter][2]= "Ledger Name";
			$ledgerst[$counter][3]= "Status";
			$ledgerst[$counter][4]= "Type";
			$ledgerst[$counter][5]= "";
			$ledgerst[$counter][6]= "Dr Amount";
			$ledgerst[$counter][7]= "";
			$ledgerst[$counter][8]= "Cr Amount";
			$ledgerst[$counter][9]= "";
			$ledgerst[$counter][10]= "Balance";
			$counter++;

			/* Opening Balance */
			list ($opbalance, $optype) = $this->Ledger_model->get_op_balance($ledger_id);
			$ledgerst[$counter] = array ("Opening Balance", "", "", "", "", "", "", "", "", convert_dc($optype), $opbalance);
			if ($optype == "D")
				$cur_balance += $opbalance;
			else
				$cur_balance -= $opbalance;
			$counter++;

			$ledgerst_q = $this->db->query("SELECT vouchers.id as vid, vouchers.number as vnumber, vouchers.date as vdate, vouchers.draft as vdraft, vouchers.type as vtype, voucher_items.amount as lamount, voucher_items.dc as ldc FROM vouchers join voucher_items on vouchers.id = voucher_items.voucher_id WHERE voucher_items.ledger_id = ? ORDER BY vouchers.date ASC, vouchers.number ASC", array($ledger_id));
			foreach ($ledgerst_q->result() as $row)
			{
				$ledgerst[$counter][0] = date_mysql_to_php($row->vdate);
				$ledgerst[$counter][1] = $row->vid;

				/* Opposite voucher name */
				if ($row->ldc == "D")
				{
	
					if ($opp_voucher_name_q = $this->db->query("SELECT * FROM voucher_items WHERE voucher_id = ? AND dc = ?",  array($row->vid, "C")))
					{
						$opp_voucher_name_d = $opp_voucher_name_q->row();
						$opp_ledger_name = $this->Ledger_model->get_name($opp_voucher_name_d->ledger_id);
						if ($opp_voucher_name_q->num_rows() > 1)
						{
							$ledgerst[$counter][2] = "(" . $opp_ledger_name . ")";
						} else {
							$ledgerst[$counter][2] = $opp_ledger_name;
						}
					}
				} else {
					if ($opp_voucher_name_q = $this->db->query("SELECT * FROM voucher_items WHERE voucher_id = ? AND dc = ?",  array($row->vid, "D")))
					{
						$opp_voucher_name_d = $opp_voucher_name_q->row();
						$opp_ledger_name = $this->Ledger_model->get_name($opp_voucher_name_d->ledger_id);
						if ($opp_voucher_name_q->num_rows() > 1)
						{
							$ledgerst[$counter][2] = "(" . $opp_ledger_name . ")";
						} else {
							$ledgerst[$counter][2] = $opp_ledger_name;
						}
					}
	
				}

				$ledgerst[$counter][3] = ($row->vdraft == 1) ? "Draft" : "Active";
				$ledgerst[$counter][4] = ucfirst(n_to_v($row->vtype));

				if ($row->ldc == "D")
				{
					if ($row->vdraft == 0)
						$cur_balance += $row->lamount;
					$ledgerst[$counter][5] = convert_dc($row->ldc);
					$ledgerst[$counter][6] = $row->lamount;
					$ledgerst[$counter][7] = "";
					$ledgerst[$counter][8] = "";

				} else {
					if ($row->vdraft == 0)
						$cur_balance -= $row->lamount;
					$ledgerst[$counter][5] = "";
					$ledgerst[$counter][6] = "";
					$ledgerst[$counter][7] = convert_dc($row->ldc);
					$ledgerst[$counter][8] = $row->lamount;
				}

				if ($row->vdraft == 0)
				{
					if ($cur_balance == 0)
					{
						$ledgerst[$counter][9] = "";
						$ledgerst[$counter][10] = 0;	
					} else if ($cur_balance < 0) {
						$ledgerst[$counter][9] = "Cr";
						$ledgerst[$counter][10] = convert_cur(-$cur_balance);
					} else {
						$ledgerst[$counter][9] = "Dr";
						$ledgerst[$counter][10] =  convert_cur($cur_balance);
					}
				} else {
					$ledgerst[$counter][9] = "";
					$ledgerst[$counter][10] = "";
				}
				$counter++;
			}

			$ledgerst[$counter][0]= "Closing Balance";
			$ledgerst[$counter][1]= "";
			$ledgerst[$counter][2]= "";
			$ledgerst[$counter][3]= "";
			$ledgerst[$counter][4]= "";
			$ledgerst[$counter][5]= "";
			$ledgerst[$counter][6]= "";
			$ledgerst[$counter][7]= "";
			$ledgerst[$counter][8]= "";
			if ($cur_balance < 0)
			{
				$ledgerst[$counter][9]= "Cr";
				$ledgerst[$counter][10]= convert_cur(-$cur_balance);
			} else {
				$ledgerst[$counter][9]= "Dr";
				$ledgerst[$counter][10]= convert_cur($cur_balance);
			}
			$counter++;

			$ledgerst[$counter] = array ("", "", "", "", "", "", "", "", "", "", "");
			$counter++;

			/* Final Opening and Closing Balance */
			$clbalance = $this->Ledger_model->get_ledger_balance($ledger_id);

			$ledgerst[$counter] = array ("Opening Balance", convert_dc($optype), $opbalance, "", "", "", "", "", "", "", "");
			$counter++;

			if ($clbalance == 0)
				$ledgerst[$counter] = array ("Closing Balance", "", 0, "", "", "", "", "", "", "", "");
			else if ($clbalance < 0)
				$ledgerst[$counter] = array ("Closing Balance", "Cr", convert_cur(-$clbalance), "", "", "", "", "", "", "", "");
			else
				$ledgerst[$counter] = array ("Closing Balance", "Dr", convert_cur($clbalance), "", "", "", "", "", "", "", "");

			$this->load->helper('csv');
			echo array_to_csv($ledgerst, "ledgerst.csv");
			return;
		}
		return;
	}
}

/* End of file report.php */
/* Location: ./system/application/controllers/report.php */
