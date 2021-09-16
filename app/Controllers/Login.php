<?php

namespace App\Controllers;

class Login extends BaseController
{
	public function index()
	{
		$this->migration = new Migration();
		if($this->Employee->is_logged_in())
		{
			redirect('home');
		}
		else
		{
			$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

			$this->form_validation->set_rules('username', 'lang:login_username', 'required|callback_login_check');


			if($this->config->get('gcaptcha_enable'))
			{
				$this->form_validation->set_rules('g-recaptcha-response', 'lang:login_gcaptcha', 'required|callback_gcaptcha_check');
			}

			if($this->form_validation->run() == FALSE)
			{
				echo view('login');
			}
			else
			{
				redirect('home');
			}
		}
	}

	public function login_check($username)
	{
		$password = $this->request->getPost('password');

		if(!$this->_installation_check())
		{
			$this->form_validation->set_message('login_check', lang('Login.invalid_installation'));

			return FALSE;
		}

		if (!$this->migration->is_latest())
		{
			set_time_limit(3600);
			// trigger any required upgrade before starting the application
			$this->migration->latest();
		}

		if(!$this->Employee->login($username, $password))
		{
			$this->form_validation->set_message('login_check', lang('Login.invalid_username_and_password'));

			return FALSE;
		}

		return TRUE;
	}

	public function gcaptcha_check($recaptchaResponse)
	{
		$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $this->config->get('gcaptcha_secret_key') . '&response=' . $recaptchaResponse . '&remoteip=' . $this->input->ip_address();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		curl_close($ch);

		$status = json_decode($result, TRUE);

		if(empty($status['success']))
		{
			$this->form_validation->set_message('gcaptcha_check', lang('Login.invalid_gcaptcha'));

			return FALSE;
		}

		return TRUE;
	}

	private function _installation_check()
	{
		// get PHP extensions and check that the required ones are installed
		$extensions = implode(', ', get_loaded_extensions());
		$keys = ['bcmath', 'intl', 'gd', 'openssl', 'mbstring', 'curl');
		$pattern = '/';
		foreach($keys as $key) 
		{
			$pattern .= '(?=.*\b' . preg_quote($key, '/') . '\b)';
		}
		$pattern .= '/i';
		$result = preg_match($pattern, $extensions);

		if(!$result)
		{
			error_log('Check your php.ini');
			error_log('PHP installed extensions: ' . $extensions);
			error_log('PHP required extensions: ' . implode(', ', $keys));
		}

		return $result;
	}
}
?>
