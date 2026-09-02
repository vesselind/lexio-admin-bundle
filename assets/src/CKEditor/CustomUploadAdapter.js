export default class CustomUploadAdapter {
    constructor(loader, uploadUrl) {
        // CKEditor 5's FileLoader instance.
        this.loader = loader;
        this.xhr = null;
        this.uploadUrl = uploadUrl;
    }

    // Starts the upload process.
    upload() {
        let url = this.uploadUrl,
            self = this;

        return this.loader.file
            .then( uploadedFile => {
                return new Promise( ( resolve, reject ) => {
                    const data = new FormData();
                    data.append( 'upload', uploadedFile );

                    self.xhr = fetch(url, {
                        method: 'POST',
                        body: data
                    })
                    .then(response => response.json())
                    .then(data => {
                            resolve({
                            default: data.url
                            });
                        })
                    .catch(error => {
                        reject(error && error.detail ? error.detail : 'Upload failed.');
                    });
                    ;
                } );
            } );
    }

    // Aborts the upload process.
    abort() {
        if (this.xhr) {
            this.xhr.abort();
        }
    }
}