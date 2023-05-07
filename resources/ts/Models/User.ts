import Model, {
  ModelAttributes,
  ModelRelations
} from '@osm/Models/Model';

export interface UserAttributes extends ModelAttributes {
  username: string;
  email: string;
}

export interface UserRelations extends ModelRelations {
  // Notifications: DatabaseNotifications
}

export default class User extends Model<UserAttributes, UserRelations> {}
