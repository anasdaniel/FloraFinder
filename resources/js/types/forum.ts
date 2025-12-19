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
    content?: string;
    image?: string | null;
    likes_count: number;
    shares_count: number;
    is_liked_by_user: boolean;
    posts_count?: number;
    created_at: string;
    updated_at: string;
    user: { id: number; name: string; avatar?: string };
    posts: ForumPost[];
    tags?: Array<{ id: number; tag_name: string; tag_category?: string }>;
}
