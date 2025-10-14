import { defineStore } from 'pinia'

export const useConfirmStore = defineStore('confirm', {
    state: () => ({
        open: false,
        title: 'Are you sure?',
        message: '',
        confirmText: 'OK',
        cancelText: 'Cancel',
        variant: 'danger', // 'primary' | 'danger' | 'warning'
        _resolver: null,
    }),
    actions: {
        ask(opts = {}) {
            return new Promise((resolve) => {
                this.title       = opts.title ?? 'Are you sure?'
                this.message     = opts.message ?? ''
                this.confirmText = opts.confirmText ?? 'OK'
                this.cancelText  = opts.cancelText  ?? 'Cancel'
                this.variant     = opts.variant ?? 'danger'
                this._resolver   = resolve
                this.open = true
            })
        },
        confirm() {
            this.open = false
            this._resolver?.(true)
            this._resolver = null
        },
        cancel() {
            this.open = false
            this._resolver?.(false)
            this._resolver = null
        },
    },
})
