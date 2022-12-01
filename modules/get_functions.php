<?php
	function getData($method): stdClass
    {
		$data = new stdClass();
		if ($method != "GET")
		{
			$data->body = json_decode(file_get_contents('php://input'));
		}
		$data->parameters = [];
		$dataGet = $_GET;
		foreach ($dataGet as $key => $value)
		{
			if ($key != "q")
			{
				$data->parameters[$key] = $value;
			}
		}
		return $data;
	}

	function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}