// resources/js/services/shareService.js
import axios from '@/http';

export const shareService = {
    /* ---------------------- Topics ---------------------- */
    async listTopics() {
        const { data } = await axios.get('share/topics');
        return data?.data ?? {};
    },

    async createTopic(payload) {
        // payload: { title, currency }
        const { data } = await axios.post('share/topics', payload);
        return data?.data ?? {};
    },

    // (Optional) minimal public info for non-members – backend may return title/status
    // Safe to call even if not implemented; caller should handle 404/501 gracefully.
    async getTopicPublic(topicId) {
        const { data } = await axios.get(`share/topics/${topicId}`);
        return data?.data ?? {};
    },

    /* ---------------- Invite / Join / Leave ---------------- */
    async rotateInvite(topicId) {
        const { data } = await axios.post(`share/topics/${topicId}/invite/rotate`);
        return data?.data ?? {};
    },

    // Join by invite token — creates/returns a pending request
    async joinByToken(token) {
        const { data } = await axios.post(`share/join/${token}`);
        return data?.data ?? {};
    },

    async listMembers(topicId) {
        const { data } = await axios.get(`share/topics/${topicId}/members`);
        // { members, invite_token, is_owner, status }
        return data?.data ?? {};
    },

    async leaveTopic(topicId) {
        const { data } = await axios.post(`share/topics/${topicId}/leave`);
        // backend returns { message: 'Left topic successfully.' }
        return data?.data ?? data ?? {};
    },

    /* ---------------------- Expenses ---------------------- */
    async listExpenses(topicId, params = {}) {
        const { data } = await axios.get(`share/topics/${topicId}/expenses`, { params });
        // { expenses, status, currency }
        return data?.data ?? {};
    },

    async createExpense(topicId, payload) {
        const { data } = await axios.post(`share/topics/${topicId}/expenses`, payload);
        // { expense_id }
        return data?.data ?? {};
    },

    async deleteExpense(topicId, expenseId) {
        const { data } = await axios.delete(`share/topics/${topicId}/expenses/${expenseId}`);
        // { deleted: true }
        return data?.data ?? {};
    },

    /* ---------------------- Balances ---------------------- */
    async listBalances(topicId) {
        const { data } = await axios.get(`share/topics/${topicId}/balances`);
        // { members, balances, transfers }
        return data?.data ?? {};
    },

    /* ---------------------- Topic status ---------------------- */
    async closeTopic(topicId) {
        const { data } = await axios.post(`share/topics/${topicId}/close`);
        // { topic_id, status: 'closed' }
        return data?.data ?? {};
    },

    async reopenTopic(topicId) {
        const { data } = await axios.post(`share/topics/${topicId}/open`);
        // { topic_id, status: 'open' }
        return data?.data ?? {};
    },

    /* ---------------------- Join Requests (owner views) ---------------------- */
    async listJoinRequests(topicId) {
        const { data } = await axios.get(`share/topics/${topicId}/join-requests`);
        // { requests: [...] }
        return data?.data ?? {};
    },

    async approveJoinRequest(topicId, requestId) {
        const { data } = await axios.post(
            `share/topics/${topicId}/join-requests/${requestId}/approve`
        );
        // { status: 'approved' }
        return data?.data ?? {};
    },

    async denyJoinRequest(topicId, requestId) {
        const { data } = await axios.post(
            `share/topics/${topicId}/join-requests/${requestId}/deny`
        );
        // { status: 'denied' }
        return data?.data ?? {};
    },

    /* ---------------------- Join Requests (requester creates) ---------------------- */
    // Creates/returns a pending request for the current user for this topic.
    // Backend response: { request_id, status: 'pending' | 'already_member' }
    async createJoinRequest(topicId, payload = {}) {
        const { data } = await axios.post(
            `share/topics/${topicId}/join-requests`,
            payload
        );
        return data?.data ?? {};
    },
};
