/**
 * SafeBadger Cryptography Engine
 * Sıfır-Bilgi (Zero-Knowledge) Uçtan Uca İstemci Şifreleme Modülü
 * Web Crypto API (PBKDF2, AES-256-GCM, SHA-256)
 */

class VaultCrypto {
  /**
   * Rastgele güvenli byte dizisi üretir
   * @param {number} length 
   * @returns {string} Hex formatında tuz (salt)
   */
  static generateSalt(length = 16) {
    const array = new Uint8Array(length);
    window.crypto.getRandomValues(array);
    return Array.from(array).map(b => b.toString(16).padStart(2, '0')).join('');
  }

  /**
   * Rastgele 12-byte IV (Initialization Vector) üretir
   */
  static generateIV() {
    const iv = new Uint8Array(12);
    window.crypto.getRandomValues(iv);
    return iv;
  }

  /**
   * ArrayBuffer'ı Base64 string'e dönüştürür
   */
  static bufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
  }

  /**
   * Base64 string'i Uint8Array'e dönüştürür
   */
  static base64ToBuffer(base64) {
    const binary = window.atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
  }

  /**
   * Hex string'i Uint8Array'e dönüştürür
   */
  static hexToBuffer(hex) {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
      bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16);
    }
    return bytes;
  }

  /**
   * Ham şifreden PBKDF2 temel anahtarı oluşturur
   */
  static async importPassword(password) {
    const enc = new TextEncoder();
    return await window.crypto.subtle.importKey(
      'raw',
      enc.encode(password),
      { name: 'PBKDF2' },
      false,
      ['deriveKey', 'deriveBits']
    );
  }

  /**
   * 1) Kasa Şifreleme Anahtarı (Master Key) Türetir (AES-256-GCM)
   * Bu anahtar ASLA sunucuya gönderilmez, yalnızca tarayıcı RAM'inde şifre çözmek için tutulur.
   */
  static async deriveMasterKey(password, saltHex) {
    const baseKey = await this.importPassword(password);
    const saltBuffer = this.hexToBuffer(saltHex);

    return await window.crypto.subtle.deriveKey(
      {
        name: 'PBKDF2',
        salt: saltBuffer,
        iterations: 100000,
        hash: 'SHA-256'
      },
      baseKey,
      { name: 'AES-GCM', length: 256 },
      false,
      ['encrypt', 'decrypt']
    );
  }

  /**
   * 2) Sunucu Kimlik Doğrulama Hash'i Türetir (Auth Hash)
   * Anahtardan bağımsız türetilir, böylece sunucu şifreleme anahtarını asla tahmin edemez.
   */
  static async deriveAuthHash(password, authSaltHex) {
    const baseKey = await this.importPassword(password);
    const saltBuffer = this.hexToBuffer(authSaltHex);

    const authKeyBits = await window.crypto.subtle.deriveBits(
      {
        name: 'PBKDF2',
        salt: saltBuffer,
        iterations: 100000,
        hash: 'SHA-256'
      },
      baseKey,
      256
    );

    // SHA-256 ile özetle
    const hashBuffer = await window.crypto.subtle.digest('SHA-256', authKeyBits);
    return Array.from(new Uint8Array(hashBuffer)).map(b => b.toString(16).padStart(2, '0')).join('');
  }

  /**
   * Veriyi AES-256-GCM ile şifreler
   * @param {Object|string} data 
   * @param {CryptoKey} cryptoKey 
   * @returns {Promise<{cipherText: string, iv: string}>}
   */
  static async encrypt(data, cryptoKey) {
    const plainText = typeof data === 'string' ? data : JSON.stringify(data);
    const enc = new TextEncoder();
    const encoded = enc.encode(plainText);
    const iv = this.generateIV();

    const cipherBuffer = await window.crypto.subtle.encrypt(
      {
        name: 'AES-GCM',
        iv: iv
      },
      cryptoKey,
      encoded
    );

    return {
      cipherText: this.bufferToBase64(cipherBuffer),
      iv: this.bufferToBase64(iv)
    };
  }

  /**
   * AES-256-GCM ile şifrelenmiş veriyi çözer
   * @param {string} cipherTextBase64 
   * @param {string} ivBase64 
   * @param {CryptoKey} cryptoKey 
   * @returns {Promise<any>}
   */
  static async decrypt(cipherTextBase64, ivBase64, cryptoKey) {
    try {
      const cipherBuffer = this.base64ToBuffer(cipherTextBase64);
      const ivBuffer = this.base64ToBuffer(ivBase64);

      const decryptedBuffer = await window.crypto.subtle.decrypt(
        {
          name: 'AES-GCM',
          iv: ivBuffer
        },
        cryptoKey,
        cipherBuffer
      );

      const dec = new TextDecoder();
      const text = dec.decode(decryptedBuffer);

      try {
        return JSON.parse(text);
      } catch (e) {
        return text;
      }
    } catch (err) {
      console.error('Şifre çözme hatası:', err);
      throw new Error('Veri çözülemedi. Ana parola hatalı veya veri bozulmuş.');
    }
  }

  /**
   * Güçlü Rastgele Parola Üretici
   */
  static generatePassword(options = {}) {
    const length = options.length || 18;
    const useUpper = options.uppercase !== false;
    const useLower = options.lowercase !== false;
    const useDigits = options.digits !== false;
    const useSymbols = options.symbols !== false;

    let chars = '';
    if (useUpper) chars += 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Karışıklığı önlemek için I, O hariç tutuldu
    if (useLower) chars += 'abcdefghijkmnopqrstuvwxyz'; // l hariç
    if (useDigits) chars += '23456789'; // 0 ve 1 hariç
    if (useSymbols) chars += '!@#$%^&*()-_=+[]{}|;:,.<>?';

    if (!chars) chars = 'abcdefghijkmnopqrstuvwxyz23456789';

    const randomValues = new Uint32Array(length);
    window.crypto.getRandomValues(randomValues);

    let password = '';
    for (let i = 0; i < length; i++) {
      password += chars[randomValues[i] % chars.length];
    }

    return password;
  }

  /**
   * Şifre Gücü Ölçer (Entropy bazlı puanlama)
   */
  static calculateStrength(password) {
    if (!password) return { score: 0, label: 'Boş', color: '#64748b' };
    
    let score = 0;
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (password.length >= 16) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[a-z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;

    if (score <= 2) return { score: 1, label: 'Zayıf', color: '#ef4444', percent: 25 };
    if (score <= 4) return { score: 2, label: 'Orta', color: '#f59e0b', percent: 50 };
    if (score <= 5) return { score: 3, label: 'Güçlü', color: '#10b981', percent: 75 };
    return { score: 4, label: 'Çok Güçlü', color: '#38bdf8', percent: 100 };
  }
}

// Global scope'a aç
window.VaultCrypto = VaultCrypto;
