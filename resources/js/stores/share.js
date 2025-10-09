import { defineStore } from 'pinia';
import { shareService } from '@/services/shareService';

export const useShareStore = defineStore('share', {
    state: () => ({
        topics: [],
        members: [],
        expenses: [],
        balances: [],
        transfers: [],
        inviteToken: null,
        isOwner: false,
        topicStatus: 'open',
        loading: false,
        error: null,
    }),

    actions: {
        async fetchTopics() {
            this.loading = true; this.error = null;
            try {
                const res = await shareService.listTopics();
                this.topics = res?.topics ?? [];
            } finally { this.loading = false; }
        },

        async fetchMembers(topicId) {
            this.loading = true; this.error = null;
            try {
                const { members, invite_token, is_owner, status } = await shareService.listMembers(topicId);
                this.members = members || [];
                this.inviteToken = invite_token || null;
                this.isOwner = !!is_owner;
                this.topicStatus = status || 'open';
            } finally { this.loading = false; }
        },

        async fetchExpenses(topicId) {
            this.loading = true; this.error = null;
            try {
                const { expenses, status } = await shareService.listExpenses(topicId);
                this.expenses = expenses ?? [];
                if (status) this.topicStatus = status; // keep in sync if backend sends it
            } finally { this.loading = false; }
        },

        async fetchBalances(topicId) {
            this.loading = true; this.error = null;
            try {
                const { balances, transfers } = await shareService.listBalances(topicId);
                this.balances = balances ?? [];
                this.transfers = transfers ?? [];
            } finally { this.loading = false; }
        },

        async createExpense(topicId, payload) {
            await shareService.createExpense(topicId, payload);
            await this.fetchExpenses(topicId);
            await this.fetchBalances(topicId);
        },

        async deleteExpense(topicId, expenseId) {
            await shareService.deleteExpense(topicId, expenseId);
            await this.fetchExpenses(topicId);
            await this.fetchBalances(topicId);
        },

        async closeTopic(topicId) {
            this.error = null;
            const res = await shareService.closeTopic(topicId);
            this.topicStatus = res?.status || 'closed';
            await this.fetchMembers(topicId); // refresh state (isOwner/status)
            await this.fetchTopics();         // keep list in sync
        },

        async reopenTopic(topicId) {
            this.error = null;
            const res = await shareService.reopenTopic(topicId);
            this.topicStatus = res?.status || 'open';
            await this.fetchMembers(topicId); // refresh state
            await this.fetchTopics();
        },
    },
});
