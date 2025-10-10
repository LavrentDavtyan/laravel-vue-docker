// resources/js/stores/share.js
import { defineStore } from 'pinia';
import { shareService } from '@/services/shareService';

export const useShareStore = defineStore('share', {
    state: () => ({
        // Lists / collections
        topics: [],
        members: [],
        expenses: [],
        balances: [],
        transfers: [],
        joinRequests: [],

        // Topic meta
        inviteToken: null,
        isOwner: false,
        topicStatus: 'open',   // 'open' | 'closed'
        topicCurrency: 'USD',

        // UI flags
        loading: false,
        error: null,
    }),

    getters: {
        // Count of pending join requests (used for a small badge in UI)
        pendingCount: (state) => (state.joinRequests || []).filter(r => r.status === 'pending').length,
    },

    actions: {
        /* ---------------------- Topics ---------------------- */
        async fetchTopics() {
            this.loading = true; this.error = null;
            try {
                const res = await shareService.listTopics();
                this.topics = res?.topics ?? [];
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to load topics.';
            } finally {
                this.loading = false;
            }
        },

        async createTopic(payload) {
            // payload: { title, currency }
            this.error = null;
            try {
                const res = await shareService.createTopic(payload);
                // after creating, refresh list so the new topic appears
                await this.fetchTopics();
                return res; // { topic_id }
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to create topic.';
                throw e;
            }
        },

        /* Invite link rotate */
        async rotateInvite(topicId) {
            this.error = null;
            try {
                const { invite_token } = await shareService.rotateInvite(topicId);
                this.inviteToken = invite_token || null;
                return { invite_token: this.inviteToken };
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to rotate invite.';
                throw e;
            }
        },

        /* Join by invite token (creates/returns a pending request) */
        async joinByToken(token) {
            this.error = null;
            try {
                return await shareService.joinByToken(token);
                // returns: { topic_id, request_id?, status: 'pending' | 'already_member' }
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to join by token.';
                throw e;
            }
        },

        /* ---------------------- Members & Topic meta ---------------------- */
        async fetchMembers(topicId) {
            this.loading = true; this.error = null;
            try {
                const { members, invite_token, is_owner, status } = await shareService.listMembers(topicId);
                this.members = members || [];
                this.inviteToken = invite_token || null;
                this.isOwner = !!is_owner;
                this.topicStatus = status || 'open';
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to load members.';
            } finally {
                this.loading = false;
            }
        },

        async leaveTopic(topicId) {
            this.error = null;
            try {
                await shareService.leaveTopic(topicId);
                // caller can redirect; here we just clear local members for safety
                this.members = [];
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to leave topic.';
                throw e;
            }
        },

        /* ---------------------- Expenses ---------------------- */
        async fetchExpenses(topicId, params = {}) {
            this.loading = true; this.error = null;
            try {
                const { expenses, status, currency } = await shareService.listExpenses(topicId, params);
                this.expenses = expenses ?? [];
                if (status)   this.topicStatus   = status;
                if (currency) this.topicCurrency = currency;
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to load expenses.';
            } finally {
                this.loading = false;
            }
        },

        async createExpense(topicId, payload) {
            this.error = null;
            try {
                await shareService.createExpense(topicId, payload);
                await this.fetchExpenses(topicId);
                await this.fetchBalances(topicId);
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to create expense.';
                throw e;
            }
        },

        async deleteExpense(topicId, expenseId) {
            this.error = null;
            try {
                await shareService.deleteExpense(topicId, expenseId);
                await this.fetchExpenses(topicId);
                await this.fetchBalances(topicId);
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to delete expense.';
                throw e;
            }
        },

        /* ---------------------- Balances ---------------------- */
        async fetchBalances(topicId) {
            this.loading = true; this.error = null;
            try {
                const { balances, transfers } = await shareService.listBalances(topicId);
                this.balances = balances ?? [];
                this.transfers = transfers ?? [];
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to load balances.';
            } finally {
                this.loading = false;
            }
        },

        /* ---------------------- Topic status ---------------------- */
        async closeTopic(topicId) {
            this.error = null;
            try {
                const res = await shareService.closeTopic(topicId);
                this.topicStatus = res?.status || 'closed';
                await this.fetchMembers(topicId); // refresh owner/status
                await this.fetchTopics();         // keep list in sync
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to close topic.';
                throw e;
            }
        },

        async reopenTopic(topicId) {
            this.error = null;
            try {
                const res = await shareService.reopenTopic(topicId);
                this.topicStatus = res?.status || 'open';
                await this.fetchMembers(topicId);
                await this.fetchTopics();
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to reopen topic.';
                throw e;
            }
        },

        /* ---------------------- Join Requests (owner views) ---------------------- */
        async fetchJoinRequests(topicId) {
            this.loading = true; this.error = null;
            try {
                const { requests } = await shareService.listJoinRequests(topicId);
                this.joinRequests = requests ?? [];
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to load join requests.';
            } finally {
                this.loading = false;
            }
        },

        async approveJoinRequest(topicId, requestId) {
            this.error = null;
            try {
                await shareService.approveJoinRequest(topicId, requestId);
                await this.fetchJoinRequests(topicId);
                await this.fetchMembers(topicId); // reflect the new member
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to approve request.';
                throw e;
            }
        },

        async denyJoinRequest(topicId, requestId) {
            this.error = null;
            try {
                await shareService.denyJoinRequest(topicId, requestId);
                await this.fetchJoinRequests(topicId);
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to deny request.';
                throw e;
            }
        },

        /* ---------------------- Join Requests (requester creates) ---------------------- */
        async createJoinRequest(topicId, payload = {}) {
            this.error = null;
            try {
                // payload can include { message }
                const res = await shareService.createJoinRequest(topicId, payload);
                return res; // { request_id, status }
            } catch (e) {
                this.error = e?.response?.data?.message || e.message || 'Failed to create join request.';
                throw e;
            }
        },
    },
});
