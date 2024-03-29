import debounce from 'lodash/debounce'

export default {
    watch: {
        // files
        selectedFile(val) {
            this.resetInput('audioFileMeta', {})

            if (val) {
                if (this.selectedFileIs('audio')) {
                    this.getAudioData(val.path)
                }

                if (this.inModal && !this.isBulkSelecting()) {
                    this.selectedFileIs('folder')
                        ? EventHub.fire('folder_selected', val.storage_path)
                        : EventHub.fire('file_selected', val.path)

                    this.selectedFileIs('folder')
                        ? EventHub.fire('folder_selected_datas', val)
                        : EventHub.fire('file_selected_datas', val)
                }

                return this.updateLs({'selectedFileName': val.name})
            }

            this.updateLs({'selectedFileName': null})
        },
        allItemsCount(val) {
            if (!val) {
                this.noFiles('show')
                this.resetInput(['selectedFile', 'currentFileIndex'])
            }
        },
        filteredItemsCount(val) {
            if (!val) {
                this.resetInput('filterName')
            }
        },

        // bulk
        bulkItemsCount(val) {
            if (val > 0 && this.inModal && !this.selectedFileIs('folder')) {
                let ids = this.bulkList.map((e) => e.id)
                EventHub.fire('multi_file_selected', ids)
            }

            if (val > 1 && !this.bulkSelectAll) {
                this.bulkSelectAll = true
            }
        },
        bulkSelect(val) {
            this.uploadArea = false

            if (!val) {
                this.firstMeta = false
            }
        },

        // move
        movableItemsCount(val) {
            if (!val && this.isActiveModal('move_file_modal')) {
                return this.toggleModal()
            }
        },

        // ls
        useRandomNamesForUpload(val) {
            this.updateLs({'useRandomNamesForUpload': val})
        },
        folders(val) {
            this.updateLs({'folders': val})
        },
        foldersIds(val) {
            this.updateLs({'foldersIds': val})
        },
        toolBar(val) {
            this.updateLs({'toolBar': val})
        },
        dirBookmarks(val) {
            this.updateLs({'dirBookmarks': val})
        },

        // filter
        filterName(val) {
            if (val) {
                this.showFilesOfType(val)
            } else {
                this.resetInput('filterdFilesList', [])
                this.selectFirst()
            }
        },
        sortName(val) {
            if (!this.isBulkSelecting()) {
                this.selectFirst()
            }
        },

        // search
        searchFor: debounce(function (val) {
            if (!this.isBulkSelecting()) {
                this.selectFirst()
            }
            if (val) {
                this.searchFiles(val)
                this.updateSearchCount()
            } else {
                this.getFiles();
                this.resetInput('searchItemsCount')
                this.noSearch('hide')
                this.selectFirstInBulkList()
            }
        }, 500),
        searchItemsCount(val) {
            if (this.allItemsCount == undefined || val == this.allItemsCount) {
                this.resetInput('searchItemsCount')
            }
        },

        // progress
        showProgress(val) {
            if (val) {
                this.uploadArea = false
                this.infoSidebar = false
                this.isLoading = true
                this.noFiles('hide')
                this.loadingFiles('show')
            } else {
                this.isLoading = false
                this.loadingFiles('hide')
                this.smallScreenHelper()
            }
        },

        // misc
        infoSidebar(val) {
            this.$nextTick(() => setTimeout(this.scrollOnLoad, 150))
        },
        no_files(val) {
            if (val) this.isLoading = false
        },
        activeModal(val) {
            let ref

            switch (val) {
                case 'new_folder_modal':
                    ref = 'new_folder_modal_input'
                    break
                case 'rename_file_modal':
                    ref = 'rename_file_modal_input'
                    break
                case 'edit_metas_modal':
                    this.newMetas = this.selectedFile.metas;
                    ref = 'edit_metas_modal_title_input'
                    break
                case 'confirm_delete_modal':
                    ref = 'confirm_delete_modal_submit'
                    break
                case 'move_file_modal':
                    ref = 'move_file_modal_submit'
                    break
                case 'save_link_modal':
                    ref = 'save_link_modal_input'
                    break
                default:
                    ref = null
            }

            if (ref) {
                this.$nextTick(() => this.$refs[ref].focus())
            }
        }
    }
}
