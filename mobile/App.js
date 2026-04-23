import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import axios from 'axios';

const API_URL = 'http://TU_IP_LOCAL:80/SistemaSAT/api';

export default function App() {
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(false);

  if (!token) {
    return <LoginScreen onLogin={setToken} />;
  }

  return <MainScreen token={token} onLogout={() => setToken(null)} />;
}

function LoginScreen({ onLogin }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Ingrese email y contraseña');
      return;
    }
    
    setLoading(true);
    try {
      const response = await axios.post(`${API_URL}/login.php`, {
        email,
        password
      });
      
      if (response.data.success) {
        onLogin(response.data.token);
      } else {
        Alert.alert('Error', response.data.error || 'Login fallido');
      }
    } catch (error) {
      Alert.alert('Error', 'No se pudo conectar al servidor');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Sistema SAT</Text>
      <Text style={styles.subtitle}>Iniciar Sesión</Text>
      
      <TextInput
        style={styles.input}
        placeholder="Email"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
      />
      
      <TextInput
        style={styles.input}
        placeholder="Contraseña"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
      />
      
      <TouchableOpacity style={styles.button} onPress={handleLogin} disabled={loading}>
        {loading ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.buttonText}>Ingresar</Text>
        )}
      </TouchableOpacity>
    </View>
  );
}

function MainScreen({ token, onLogout }) {
  const [screen, setScreen] = useState('dashboard');
  const [ordenes, setOrdenes] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (screen === 'ordenes') {
      loadOrdenes();
    }
  }, [screen]);

  const loadOrdenes = async () => {
    setLoading(true);
    try {
      const response = await axios.get(`${API_URL}/ordenes.php?token=${token}`);
      setOrdenes(response.data);
    } catch (error) {
      Alert.alert('Error', 'No se pudieron cargar las órdenes');
    } finally {
      setLoading(false);
    }
  };

  const renderScreen = () => {
    switch (screen) {
      case 'dashboard':
        return <DashboardScreen onNavigate={setScreen} />;
      case 'ordenes':
        return (
          <OrdenesScreen 
            ordenes={ordenes} 
            loading={loading} 
            onRefresh={loadOrdenes} 
          />
        );
      case 'perfil':
        return <PerfilScreen onLogout={onLogout} />;
      default:
        return <DashboardScreen onNavigate={setScreen} />;
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        {renderScreen()}
      </View>
      
      <View style={styles.tabBar}>
        <TouchableOpacity style={styles.tab} onPress={() => setScreen('dashboard')}>
          <Text style={[styles.tabText, screen === 'dashboard' && styles.tabActive]}>🏠</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.tab} onPress={() => setScreen('ordenes')}>
          <Text style={[styles.tabText, screen === 'ordenes' && styles.tabActive]}>📋</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.tab} onPress={() => setScreen('perfil')}>
          <Text style={[styles.tabText, screen === 'perfil' && styles.tabActive]}>👤</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

function DashboardScreen({ onNavigate }) {
  return (
    <View>
      <Text style={styles.title}>Dashboard</Text>
      
      <TouchableOpacity style={styles.card} onPress={() => onNavigate('ordenes')}>
        <Text style={styles.cardTitle}>📋 Órdenes</Text>
        <Text style={styles.cardSubtitle}>Ver todas las órdenes de servicio</Text>
      </TouchableOpacity>
      
      <TouchableOpacity style={styles.card}>
        <Text style={styles.cardTitle}>📊 Reportes</Text>
        <Text style={styles.cardSubtitle}>Estadísticas del sistema</Text>
      </TouchableOpacity>
    </View>
  );
}

function OrdenesScreen({ ordenes, loading, onRefresh }) {
  const renderItem = ({ item }) => (
    <TouchableOpacity style={styles.item}>
      <View style={styles.itemHeader}>
        <Text style={styles.itemTitle}>{item.codigo}</Text>
        <Text style={styles.badge}>{item.estado}</Text>
      </View>
      <Text style={styles.itemText}>{item.cliente_nombre}</Text>
      <Text style={styles.itemText}>{item.marca} {item.modelo}</Text>
      <Text style={styles.itemDate}>{item.fecha_ingreso}</Text>
    </TouchableOpacity>
  );

  if (loading) {
    return <ActivityIndicator size="large" style={styles.loader} />;
  }

  return (
    <View>
      <Text style={styles.title}>Órdenes de Servicio</Text>
      <FlatList
        data={ordenes}
        renderItem={renderItem}
        keyExtractor={item => item.id.toString()}
        refreshing={loading}
        onRefresh={onRefresh}
      />
    </View>
  );
}

function PerfilScreen({ onLogout }) {
  return (
    <View>
      <Text style={styles.title}>Mi Perfil</Text>
      <TouchableOpacity style={styles.button} onPress={onLogout}>
        <Text style={styles.buttonText}>Cerrar Sesión</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  content: {
    flex: 1,
    padding: 20,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 20,
    color: '#333',
  },
  subtitle: {
    fontSize: 18,
    color: '#666',
    marginBottom: 30,
  },
  input: {
    backgroundColor: '#fff',
    padding: 15,
    borderRadius: 10,
    marginBottom: 15,
    fontSize: 16,
  },
  button: {
    backgroundColor: '#0d6efd',
    padding: 15,
    borderRadius: 10,
    alignItems: 'center',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  card: {
    backgroundColor: '#fff',
    padding: 20,
    borderRadius: 10,
    marginBottom: 15,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  cardSubtitle: {
    fontSize: 14,
    color: '#666',
    marginTop: 5,
  },
  item: {
    backgroundColor: '#fff',
    padding: 15,
    borderRadius: 10,
    marginBottom: 10,
  },
  itemHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 5,
  },
  itemTitle: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  badge: {
    fontSize: 12,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 10,
    backgroundColor: '#0d6efd',
    color: '#fff',
  },
  itemText: {
    fontSize: 14,
    color: '#666',
  },
  itemDate: {
    fontSize: 12,
    color: '#999',
    marginTop: 5,
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#eee',
    paddingVertical: 10,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
  },
  tabText: {
    fontSize: 24,
    opacity: 0.5,
  },
  tabActive: {
    opacity: 1,
  },
  loader: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
});