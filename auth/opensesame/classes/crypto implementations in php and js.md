# JAVACRIPT

```javascript
async function encrypt(text, password) {
  const encoder = new TextEncoder();
  const data = encoder.encode(text);
  const passwordData = encoder.encode(password);
  const iv = window.crypto.getRandomValues(new Uint8Array(16));
  const keyData = await window.crypto.subtle.digest('SHA-256', passwordData);
  const key = await window.crypto.subtle.importKey(
    'raw',
    keyData,
    { name: 'AES-CBC' },
    false,
    ['encrypt']
  );
  const cipher = await window.crypto.subtle.encrypt({name: 'AES-CBC', iv}, key, data);
  const encryptedBytes = new Uint8Array(cipher);
  const encryptedBase64 = btoa(String.fromCharCode(...encryptedBytes));
  return `${btoa(String.fromCharCode(...iv))}:${encryptedBase64}`;
}

async function decrypt(text, password) {
  const [ivBase64, encryptedBase64] = text.split(':');
  const iv = Uint8Array.from(atob(ivBase64), c => c.charCodeAt(0));
  const encrypted = Uint8Array.from(atob(encryptedBase64), c => c.charCodeAt(0));
  const passwordData = new TextEncoder().encode(password);
  const keyData = await window.crypto.subtle.digest('SHA-256', passwordData);
  const key = await window.crypto.subtle.importKey(
    'raw',
    keyData,
    { name: 'AES-CBC' },
    false,
    ['decrypt']
  );
  const decrypted = await window.crypto.subtle.decrypt({name: 'AES-CBC', iv}, key, encrypted);
  const decoder = new TextDecoder();
  return decoder.decode(decrypted);
}
```

# PHP

```php
function encrypt($text, $password) {
  $data = utf8_encode($text);
  $passwordData = utf8_encode($password);
  $iv = random_bytes(16);
  $keyData = hash('sha256', $passwordData, true);
  $cipher = openssl_encrypt($data, 'aes-256-cbc', $keyData, OPENSSL_RAW_DATA, $iv);
  $encrypted = base64_encode($iv) . ':' . base64_encode($cipher);
  $encrypted = str_replace(['+', '/', '='], ['-', '_', ''],$encrypted);
  return $encrypted;
}

function decrypt($text, $password) {
  $text = str_replace(['-', '_'], ['+', '/'], $text);
  list($ivBase64, $encryptedBase64) = explode(':', $text);
  $iv = base64_decode($ivBase64);
  $encrypted = base64_decode($encryptedBase64);
  $passwordData = utf8_encode($password);
  $keyData = hash('sha256', $passwordData, true);
  $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $keyData, OPENSSL_RAW_DATA, $iv);
  $text = utf8_decode($decrypted);
  return $text;
}
```