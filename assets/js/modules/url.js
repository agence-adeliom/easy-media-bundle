export default {
    methods: {
        getUrlWithoutQuery() {
            let params = new URLSearchParams(location.search)

            return params.has('folder_id')
                ? location.href.replace(new RegExp(`[?&]folder_id=${params.get('folder_id')}`), '')
                : location.href
        },
        clearUrlQuery() {
            history.replaceState(null, null, this.getUrlWithoutQuery())
        },
        getPathFromUrl() {
            return new Promise((resolve) => {
                if (!this.inModal) {
                    let params = new URLSearchParams(location.search)
                    this.foldersIds = params.has('folder_id')
                        ? this.arrayFilter(params.get('folder_id').replace(/#/g, '').split('/'))
                        : []
                }

                return resolve()
            })
        },
        updatePageUrl() {
            if (!this.inModal && !this.restrictModeIsOn) {
                let full_url = this.getUrlWithoutQuery()
                let current_qs = new URL(full_url).search
                let params = new URLSearchParams(current_qs)
                let base = full_url.replace(current_qs, '')
                let folders = this.folders
                let id = null;
                if(this.foldersIds.length){
                    id = this.foldersIds[this.foldersIds.length - 1];
                }

                if (id) {
                    params.append('folder_id', id)
                }

                history.pushState(
                    null,
                    null,
                    current_qs
                        ? `${base}?${params.toString()}`
                        : full_url
                )
            }
        },
        urlNavigation(e) {
            if (!this.inModal) {
                this.getPathFromUrl().then(this.getFiles())
            }
        }
    }
}
