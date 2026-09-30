/*
 * Lógica de firma del lado del Cliente (Browser)
 * Requiere: jsrsasign-all-min.js
 */

// TU LLAVE PRIVADA (La que corresponde al certificado GRUPO5)
var privateKey = "-----BEGIN PRIVATE KEY-----\n" +
"MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC707oFBeJZmihU\n" +
"sesYmJhwBsI2gbAsqZ73y/7kYimyDVVLDjAm9vprXHUBHEiKqpCMS0yukIl7XQYo\n" +
"UX55wVxsqpZ6lf1hjwf9bm/i09Q2sUqz5Vo+5O8M1AL1dxTcpHphn8OB58x+4bBE\n" +
"gQMWKMU9jw6kWdzfKyD75UvCEJ1/Keh4fYKqxgPxrsQwJqCQzrZnVl0ZWjEjnafw\n" +
"ZFgN4aj3/2200h60vkk2bwbYxwr7DkvjqXSYeVjs8qrtq7MEcEnoMXh3DXvZBcq6\n" +
"UFWe2QNrsjFPYnFsgZ5z8Sq4K8a5BOhDV62XBXxNSb8I3L15VAxc8nY76t8p2Mbn\n" +
"fb4atM4TAgMBAAECggEAC0JLVc6+/UEE5uLNZWmMRbbaAHtwrJS0X0U+r8EEn9Q1\n" +
"xyyADW3jn/joWJsx7AICbS58V8B5RUhTvKn562GAYVEueQWxrR3uTC5JDa9F+stQ\n" +
"34zBhqChhcLVtJIhUYKYhW6WwRw8gWSU9N5I6ZSjEigXqBls6IHE1eSuUGE/l6Y4\n" +
"mrmnYX4oAmzIA2j5cgnkxf8Yi/yff5za2R6qzUglayUeciaJ2ufmVNZyhhpjyLgD\n" +
"s0LPsZkQwBYqBvL1bXE3ymVD5VXUVdrvLs2BmzEBtaR8HRHElDN6j5j/c1blwD+G\n" +
"m4egG9+5+5Hk71pjMyBj2kh5nPjn4MobUGtCS7UmXQKBgQDrad5AFrPbOTI56hLK\n" +
"PlDZf2kLarXfr/7OneCAlxyApsRjlWQmUkdW1Rc8LvaMA1eOvXdwqli0MFhsDlH7\n" +
"Ohsu4UrsM1kBPyhyU/GhV0ubtcYzy9C0fUxBziTKwd89J47QG/gCqZUrPzGPPNsG\n" +
"wwybhhDkx9tsKA/slX1D2zxedQKBgQDMQI3aqd2724a1ueikcA3sCAd63Gi51JAP\n" +
"HhbCxwYldrKtHXFscmF9+vvBX8Kfiv/b6+/w8MhKNn+3Un9kKGNtOZhdHHYKNlci\n" +
"yU92Kh+C15EZ7Bn0llHjE+P2HMnomgMvgwNJmpenJqttIiqJOd+ic3vBQVQLgzPx\n" +
"xynX2//5ZwKBgQC9HnK+l75rihpwbjwkH0XCRNn1owdzKScRg8F8bGkobVDuE3C+\n" +
"XHEVL+EXpz7HolOvx0PxzdevAfu26xYvNjHqSnFvKZ0CJGPw3MEL9V43PCN7Lux+\n" +
"Yv5lCx+Bh5g4v9O6Gg32zZeZg43j3WazmvRA6MYflPMQg2qvjDRy0+g+lQKBgAUQ\n" +
"gLPwTFAxJEjzQCJ7qMy2ckEIoAwjiiLl0xinSK67H7kNJtfSijpOc9manz/HeYqh\n" +
"cgSeY8j7SvVntULtgXGe4PlGEGM2b7isFy1N5BQhdjaXVRlsdMFVp+xdUGBVGUkf\n" +
"TYCQtbvuSIffNgDLD5F+nY3wKb5TVYFkN9RjXxh3AoGAFtgiS0BT3M9l7OvwWva+\n" +
"wN7/zUJntcufETwnJ6jUvU4rpCVqnZiBXochi5hgvuTkHXXzw7pNq9iObmE+qdQ4\n" +
"xlTJzyoez7WpTh1QEmdewP2HF9o/VnXoRkc3KjgMBkK+iL26FeM/o6JDZGuYn7AI\n" +
"nSRv+rldGSVytKpw5XTsIx8=\n" +
"-----END PRIVATE KEY-----";



// Algoritmo de firma
qz.security.setSignatureAlgorithm("SHA512"); 

// Sobreescribimos la promesa de firma para que use la librería JS local
qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
        try {
            // Usamos la librería jsrsasign para firmar localmente
            var pk = KEYUTIL.getKey(privateKey);
            var sig = new KJUR.crypto.Signature({"alg": "SHA512withRSA"});
            sig.init(pk); 
            sig.updateString(toSign);
            var hex = sig.sign();
            console.log("Firma generada localmente en JS");
            resolve(stob64(hextorstr(hex)));
        } catch (err) {
            console.error(err);
            reject(err);
        }
    };
});