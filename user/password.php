<?php
/*
 * Password hashing with PBKDF2. Changes:
 * - added another salt: PASSWORD_SALT from config.php (in case only the database is compromised)
 * - added temporary handling of old passwords (those with prefix 'old:0::')
 * - added function generateRandomPassword($length) to generate temporary random passwords
 * - added function passwordNeedsRehash($hash)
 * Author: havoc AT defuse.ca, Licence: public domain
 * www: https://defuse.ca/php-pbkdf2.htm
 */

// These constants may be changed without breaking existing hashes.
define("PBKDF2_HASH_ALGORITHM", "sha512");
define("PBKDF2_ITERATIONS", 1000);
define("PBKDF2_SALT_BYTE_SIZE", 48);
define("PBKDF2_HASH_BYTE_SIZE", 48);

define("HASH_SECTIONS", 4);
define("HASH_ALGORITHM_INDEX", 0);
define("HASH_ITERATION_INDEX", 1);
define("HASH_SALT_INDEX", 2);
define("HASH_PBKDF2_INDEX", 3);

function createPasswordHash($password)
{
	// format: algorithm:iterations:salt:hash
	$salt = base64_encode(mcrypt_create_iv(PBKDF2_SALT_BYTE_SIZE, MCRYPT_DEV_URANDOM));
	return PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" .  $salt . ":" . 
		base64_encode(pbkdf2(
			PBKDF2_HASH_ALGORITHM,
			PASSWORD_SALT . $password,
			$salt,
			PBKDF2_ITERATIONS,
			PBKDF2_HASH_BYTE_SIZE,
			true
		));
}

function validatePassword($password, $correct_hash)
{
	// DEPRECATED: The root password is hashed this way so it can be set in config.php,
	// installation scripts only have to write 'rootpassword' instead of a sha1().
	//if ($correct_hash === 'rootpassword')
	//	return slow_equals(ROOT_PASSWORD, $password);
	
	$params = explode(":", $correct_hash);
	if (count($params) < HASH_SECTIONS)
		return false; 

	// DEPRECATED: Accept old passwords
	if ($params[HASH_ALGORITHM_INDEX] === 'old')
		return slow_equals($params[HASH_PBKDF2_INDEX], sha1(OLD_PASSWORD_SALT . $password));

	$pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
	return slow_equals(
		$pbkdf2,
		pbkdf2(
			$params[HASH_ALGORITHM_INDEX],
			PASSWORD_SALT . $password,
			$params[HASH_SALT_INDEX],
			(int)$params[HASH_ITERATION_INDEX],
			strlen($pbkdf2),
			true
		)
	);
}

function generateRandomPassword($length)
{
	$random = mcrypt_create_iv(PBKDF2_SALT_BYTE_SIZE, MCRYPT_DEV_URANDOM);
	return substr(sha1(base64_encode($random)), 0, $length);
}

function passwordNeedsRehash($hash)
{
	$params = explode(":", $hash);
	if (count($params) < HASH_SECTIONS)
		return true; 
	if ($params[HASH_ALGORITHM_INDEX] === 'old')
		return true; 
	return false;
}

// Compares two strings $a and $b in length-constant time.
function slow_equals($a, $b)
{
	$diff = strlen($a) ^ strlen($b);
	for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
	{
		$diff |= ord($a[$i]) ^ ord($b[$i]);
	}
	return $diff === 0; 
}

/*
 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
 * $algorithm - The hash algorithm to use. Recommended: SHA256
 * $password - The password.
 * $salt - A salt that is unique to the password.
 * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
 * $key_length - The length of the derived key in bytes.
 * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
 * Returns: A $key_length-byte key derived from the password and salt.
 *
 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
 *
 * This implementation of PBKDF2 was originally created by https://defuse.ca
 * With improvements by http://www.variations-of-shadow.com
 */
function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
{
	$algorithm = strtolower($algorithm);
	if(!in_array($algorithm, hash_algos(), true))
		die('PBKDF2 ERROR: Invalid hash algorithm.');
	if($count <= 0 || $key_length <= 0)
		die('PBKDF2 ERROR: Invalid parameters.');

	$hash_length = strlen(hash($algorithm, "", true));
	$block_count = ceil($key_length / $hash_length);

	$output = "";
	for($i = 1; $i <= $block_count; $i++) {
		// $i encoded as 4 bytes, big endian.
		$last = $salt . pack("N", $i);
		// first iteration
		$last = $xorsum = hash_hmac($algorithm, $last, $password, true);
		// perform the other $count - 1 iterations
		for ($j = 1; $j < $count; $j++) {
			$xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
		}
		$output .= $xorsum;
	}

	if($raw_output)
		return substr($output, 0, $key_length);
	else
		return bin2hex(substr($output, 0, $key_length));
}

