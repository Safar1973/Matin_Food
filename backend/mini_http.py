from http.server import SimpleHTTPRequestHandler, HTTPServer
HTTPServer(("127.0.0.1", 5050), SimpleHTTPRequestHandler).serve_forever()
