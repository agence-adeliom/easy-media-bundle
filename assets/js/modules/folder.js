export default {
    methods: {
        // navigation
        openFolder(folder) {
            if (this.fileTypeIs(folder, 'folder')) {
                this.folders.push(folder.name)
                this.foldersIds.push(folder.id);
                this.$nextTick(() => {
                    this.getFiles().then(this.updatePageUrl())
                })
            }else if (this.inModal){
                EventHub.fire('file_modal_selected', folder);
            }
        },
        goToPrevFolder() {
            EventHub.fire('stopHammerPropagate')

            if (this.restrictPathIsCurrent) return

            let length = this.folders.length

            if (length) {
                let index = length - 1
                let folders = this.folders
                let foldersIds = this.foldersIds
                let prev_folder_name = folders[index]
                this.folders = folders.splice(0, index)
                this.foldersIds = foldersIds.splice(0, index)

                this.$nextTick(() => {
                    this.getFiles(prev_folder_name).then(this.updatePageUrl())
                })
            }
        },
        goToFolder(index) {
            if (!this.isBulkSelecting() && !this.waitingForUpload) {
                let folders = this.folders
                let foldersIds = this.foldersIds
                let prev_folder_name = null

                if (this.restrictModeIsOn) {
                    if (index == 0) {
                        // go home
                        prev_folder_name = this.pathBarDirsList[index]
                        this.resolveRestrictFolders()
                    } else {
                        // go by index
                        index = index + this.getRestrictedPathArray().length
                        prev_folder_name = folders[index]
                        this.folders = folders.splice(0, index)
                        this.foldersIds = foldersIds.splice(0, index)
                    }
                } else {
                    prev_folder_name = folders[index]
                    this.folders = folders.splice(0, index)
                    this.foldersIds = foldersIds.splice(0, index)
                }

                this.$nextTick(() => {
                    this.getFiles(prev_folder_name).then(this.updatePageUrl())
                })
            }
        }
    },
    computed: {
        pathBarDirsList() {
            let folders = this.folders.join('/')
            let rest = this.resrtictPath
            let list = this.restrictPathIsCurrent
                ? []
                : this.arrayFilter(folders.replace(rest, '').split('/'))

            return this.restrictModeIsOn
                ? list
                : this.folders
        }
    }
}
