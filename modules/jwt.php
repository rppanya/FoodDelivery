<?php
	function generateToken($email): string {

		$header = ['alg' => 'HS256', 'typ' => 'JWT'];
		$payload = ['email' => $email];
		$secret = bin2hex(random_bytes(32));

		$now = new DateTime();
		$payload['nbf'] = $now->getTimestamp();
		$payload['exp'] = $now->getTimestamp() + 3600;
		$payload['iat'] = $now->getTimestamp();
		$payload['iss'] = "http://localhost/";
		$payload['aud'] = "http://localhost/";

		$base64Header = base64_encode(json_encode($header));
		$base64Payload = base64_encode(json_encode($payload));

		$base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
		$base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

		$secret = base64_encode($secret);
		$verifySignature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);

		$base64Signature = base64_encode($verifySignature);

		$verifySignature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

		return $base64Header . '.' . $base64Payload . '.' . $verifySignature;
	}

	function getPayload(string $token) {
		$array = explode('.', $token);
		return json_decode(base64_decode($array[1]), true);
	}

	function isExpired(string $token): bool {
		$payload = getPayload($token);
		$now = new DateTime();
		return $payload['exp'] < $now->getTimestamp();
	}

	function isValid(string $token): bool {
		$array = explode('.', $token);
		return $array[0] == 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
	}
