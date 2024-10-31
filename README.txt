ChunksUpload

To improve:

1. On frontend:
If file size is less than chunk size, just send the whole file at once.
No chunking parameter is needed for Dropzone.

2. Backend:
Handle case where chunks are not uploaded in order (for example with parallel uploads)

2.1. Use laravel cache to keep state of all uploaded chunks

2.2. Use laravel job to clean up not complete chunks

Job should be scheduled when the first chunk is received.
Job should be run once.


3. CORS and CSRF

3.1. If user is authorized special CORS middleware is not needed.

3.2. Add proper CSRF in the request header



