export default {
    methods: {
        /*                Main                */
        getFiles(prev_folder = null, prev_file = null) {
            this.files.next = null
            this.resetInput(['sortName', 'filterName', 'selectedFile', 'currentFileIndex'])
            this.noFiles('hide')
            this.destroyPlyr()

            if (!this.loading_files) {
                this.isLoading = true
                this.infoSidebar = false
                this.loadingFiles('show')
            }
            let id = null;
            if(this.foldersIds.length){
                id = this.foldersIds[this.foldersIds.length - 1];
            }
            // get data
            return axios.post(this.routes.files, {
                folder: id
            }).then(({data}) => {
                // folder doesnt exist
                if (data.error) {
                    this.showNotif(data.error, 'danger');
                    this.foldersIds.pop();
                    this.folders.pop();
                    return this.getFiles(prev_folder, prev_file);
                    //return this.showNotif(data.error, 'danger')
                }

                // return data
                this.files = {
                    folder: id,
                    path: data.files.path,
                    items: data.files.items.data,
                    next: data.files.items.next_page_url
                }
                this.filesListCheck(prev_folder, prev_file)

            }).catch((err) => {
                console.error(err)
                this.isLoading = false
                this.loadingFiles('hide')
                this.ajaxError()
            })
        },
        searchFiles(value) {
            this.files.next = null
            this.resetInput(['sortName', 'filterName', 'selectedFile', 'currentFileIndex'])
            this.noFiles('hide')
            this.destroyPlyr()

            if (!this.loading_files) {
                this.isLoading = true
                this.infoSidebar = false
                this.loadingFiles('show')
            }
            let id = null;
            if(this.foldersIds.length){
                id = this.foldersIds[this.foldersIds.length - 1];
            }
            // get data
            return axios.post(this.routes.files, {
                folder: id,
                search: value
            }).then(({data}) => {
                // return data
                this.files = {
                    folder: id,
                    path: data.files.path,
                    items: data.files.items.data,
                    next: data.files.items.next_page_url
                }
                this.filesListCheck(null, null)

            }).catch((err) => {
                console.error(err)
                this.isLoading = false
                this.loadingFiles('hide')
                this.ajaxError()
            })
        },
        loadPaginatedFiles($state) {
            return axios.post(this.files.next, {
                path: this.files.path || '/'
            }).then(({data}) => {
                let next_page = data.files.items.next_page_url

                // add extra items
                this.files.items = this.files.items.concat(data.files.items.data)
                this.files.next = next_page

                next_page
                    ? $state.loaded()
                    : $state.complete()
            }).catch((err) => {
                console.error(err)
                this.ajaxError()
            })
        },

        filesListCheck(prev_folder, prev_file) {
            let files = this.files.items

            if (this.hideExt.length) {
                files = files.filter((e) => !this.checkForHiddenExt(e))
            }

            if (this.hidePath.length) {
                files = files.filter((e) => !this.checkForHiddenPath(e))
            }

            if (this.restrict && this.restrict.uploadTypes && this.restrict.uploadTypes.length) {
                files = files.filter((e) => this.checkForRestrictedTypes(e))
            }

            // we have files
            if (this.allItemsCount) {
                // check for prev
                if (prev_file || prev_folder) {
                    files.some((e, i) => {
                        if (
                            (prev_folder && e.name == prev_folder) ||
                            (prev_file && e.name == prev_file)
                        ) {
                            return this.currentFileIndex = i
                        }
                    })
                }

                this.$nextTick(() => {

                    this.files.items = files;

                    // no prev found
                    this.currentFileIndex
                        ? this.selectFirst(this.currentFileIndex)
                        : false

                    // update search
                    if (this.searchFor) {
                        this.updateSearchCount()
                    }
                })
            }

            this.isLoading = false
            this.loadingFiles('hide')
            this.smallScreenHelper()

            // we dont have files & user clicked the "refresh btn"
            this.$nextTick(() => {
                if (!this.allItemsCount && !this.no_files) {
                    this.noFiles('show')
                }
            })
        },

        /*                Tool-Bar                */
        NewFolderForm(event) {
            let action = event.target.closest("[action]").getAttribute("action");

            let folder_name = this.newFolderName
            let path = this.files.path
            let folder = this.files.folder

            if (!folder_name) {
                return this.showNotif(this.trans('no_val'), 'warning')
            }

            this.toggleLoading()

            axios.post(action, {
                folder: folder,
                new_folder_name: folder_name
            }).then(({data}) => {
                this.toggleLoading()
                this.toggleModal()
                this.resetInput('newFolderName')

                if (data.message) {
                    return this.showNotif(data.message, 'danger')
                }

                path = path || '/'
                let new_name = data.new_folder_name
                let msg = this.restrictModeIsOn
                    ? `"${new_name}"`
                    : `"${new_name}" "${path}"`

                this.showNotif(`${this.trans('create_success')} ${msg}`)
                this.isBulkSelecting() ? this.blkSlct() : false
                this.getFiles(new_name)

            }).catch((err) => {
                console.error(err)
                this.ajaxError()
            })
        },
        // edit metas
        EditMetasFileForm(event) {
            let action = event.target.closest("[action]").getAttribute("action");

            let selected = this.selectedFile
            let changed = {
                'title': this.$refs.edit_metas_modal_title_input.value,
                'alt': this.$refs.edit_metas_modal_alt_input.value,
                'description': this.$refs.edit_metas_modal_desc_input.value
            }

            if (!changed) {
                return this.showNotif(this.trans('no_val'), 'warning')
            }

            this.toggleLoading()

            axios.post(action, {
                file: selected,
                path: this.files.path,
                new_metas: changed
            }).then(({data}) => {
                this.toggleLoading()
                this.toggleModal()

                if (data.message) {
                    return this.showNotif(data.message, 'danger')
                }

                let newMetas = data.metas
                this.showNotif(`${this.trans('edit_metas_success')}`)
                selected.metas = newMetas
            }).catch((err) => {
                console.error(err)
                this.ajaxError()
            })
        },

        // rename
        RenameFileForm(event) {
            let action = event.target.closest("[action]").getAttribute("action");
            let selected = this.selectedFile
            let changed = this.newFilename
            let filename = selected.name
            let ext = this.getExtension(filename)
            let newFilename = ext == null ? changed : `${changed}.${ext}`

            if (!changed) {
                return this.showNotif(this.trans('no_val'), 'warning')
            }

            let files = [selected];

            if (!files.length) {
                return this.toggleModal()
            }

            // remove from move list if found
            if (this.inMovableList()) {
                this.removeFromMovableList(this.movableList.indexOf(selected))
            }

            this.toggleLoading()

            axios.post(action, {
                file: selected,
                path: this.files.path,
                new_filename: newFilename
            }).then(({data}) => {
                this.toggleLoading()
                this.toggleModal()

                if (data.message) {
                    return this.showNotif(data.message, 'danger')
                }

                let savedName = data.new_filename

                this.showNotif(`${this.trans('rename_success')} "${filename}" -> "${savedName}"`)
                selected.name = savedName
                selected.path = selected.path.replace(filename, savedName)
                selected.storage_path = selected.storage_path.replace(filename, savedName)

            }).catch((err) => {
                console.error(err)
                this.ajaxError()
            })
        },

        // delete
        DeleteFileForm(event) {
            let action = event.target.closest("[action]").getAttribute("action");

            let gls_item = this.global_search_item
            let files =  gls_item ? [gls_item] : this.delOrMoveList()

            if (!files.length) {
                return this.toggleModal()
            }

            this.toggleLoading()

            axios.post(action, {
                path: this.files.path,
                deleted_files: files
            }).then(({data}) => {
                this.toggleLoading()
                this.toggleModal()

                if (!this.globalSearchPanelIsVisible) {
                    data.map((item) => {
                        if (!item.success) {
                            return this.showNotif(item.message, 'danger')
                        }

                        let path = item.path
                        this.showNotif(`${this.trans('delete_success')} "${item.name}"`)
                        this.removeFromLists(path)
                    })

                    this.isBulkSelecting()
                        ? this.blkSlct()
                        : this.allItemsCount
                            ? this.selectFirst()
                            : false

                    this.$nextTick(() => {
                        if (this.searchFor) {
                            this.searchItemsCount = this.filesList.length
                        }
                    })
                } else {
                    data.map((item) => {
                        EventHub.fire('global-search-deleted', this.global_search_item.path)
                        this.resetInput('global_search_item')

                        !item.success
                            ? this.showNotif(item.message, 'danger')
                            : this.showNotif(`${this.trans('delete_success')} "${item.name}"`)
                    })
                }

                this.db('clr')

            }).catch((err) => {
                console.error(err)
                this.ajaxError()
            })
        },

        /*                Ops                */
        removeFromLists(path, reset = true) {
            if (this.filteredItemsCount) {
                this.updateListsRemove(this.filterdFilesList, path)
            }

            if (this.movableItemsCount) {
                this.updateListsRemove(this.movableList, path)
            }

            if (this.dirBookmarks.length) {
                this.updateListsRemove(this.dirBookmarks, path, 'dir')
            }

            this.updateListsRemove(this.files.items, path)

            if (reset) {
                this.resetInput(['selectedFile', 'currentFileIndex'])
            }
        },
        updateListsRemove(list, path, field_name = 'storage_path') {
            if (field_name) {
                return list.some((e, i) => {
                    if (e[field_name] == path) {
                        list.splice(i, 1)
                    }
                })
            }

            return list.some((e, i) => {
                if (e == path) {
                    list.splice(i, 1)
                }
            })
        }
    }
}
