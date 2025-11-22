// resources/js/types/forum.ts

export interface ForumPost {
    id: number;
    user_id: number;
    content: string;
    image?: string | null;
    created_at: string;
    updated_at: string;
}

export interface ForumThread {
    id: number;
    user_id: number;
    title: string;
    category: string;
    created_at: string;
    updated_at: string;
    user: { id: number; name: string };
    posts: ForumPost[];
}
